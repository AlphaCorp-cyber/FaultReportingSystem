import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor, RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, accuracy_score
from sklearn.preprocessing import LabelEncoder
import psycopg2
import json
import os
from datetime import datetime, timedelta
import warnings
warnings.filterwarnings('ignore')

class FaultPredictor:
    def __init__(self):
        self.location_encoder = LabelEncoder()
        self.category_encoder = LabelEncoder()
        self.department_encoder = LabelEncoder()
        self.frequency_model = None
        self.category_model = None
        self.location_model = None
        
    def get_database_connection(self):
        """Get database connection using environment variables"""
        return psycopg2.connect(
            host=os.getenv('PGHOST', 'localhost'),
            database=os.getenv('PGDATABASE', 'postgres'),
            user=os.getenv('PGUSER', 'postgres'),
            password=os.getenv('PGPASSWORD', ''),
            port=os.getenv('PGPORT', '5432')
        )
    
    def extract_features_from_data(self):
        """Extract historical fault data and create features"""
        conn = self.get_database_connection()
        
        # Get historical fault data
        query = """
        SELECT 
            fr.id,
            fr.reference_number,
            fr.category,
            fr.location,
            fr.assigned_department,
            fr.priority,
            fr.status,
            fr.created_at,
            fr.updated_at,
            EXTRACT(MONTH FROM fr.created_at) as month,
            EXTRACT(DOW FROM fr.created_at) as day_of_week,
            EXTRACT(HOUR FROM fr.created_at) as hour,
            CASE 
                WHEN fr.updated_at IS NOT NULL 
                THEN EXTRACT(EPOCH FROM (fr.updated_at - fr.created_at))/3600 
                ELSE 0 
            END as resolution_time_hours
        FROM fault_reports fr
        ORDER BY fr.created_at DESC
        """
        
        df = pd.read_sql_query(query, conn)
        conn.close()
        
        if df.empty:
            return pd.DataFrame()
        
        # Create additional features
        df['location_clean'] = df['location'].str.strip().str.lower()
        df['is_weekend'] = df['day_of_week'].isin([0, 6]).astype(int)
        df['is_business_hours'] = ((df['hour'] >= 8) & (df['hour'] <= 17)).astype(int)
        
        # Group by location and date to get fault frequency
        df['date'] = pd.to_datetime(df['created_at']).dt.date
        location_daily = df.groupby(['location_clean', 'date']).size().reset_index(name='daily_count')
        
        # Calculate location-based statistics
        location_stats = df.groupby('location_clean').agg({
            'id': 'count',
            'resolution_time_hours': 'mean',
            'priority': lambda x: (x == 'high').sum() / len(x)
        }).rename(columns={
            'id': 'total_faults',
            'resolution_time_hours': 'avg_resolution_time',
            'priority': 'high_priority_ratio'
        }).reset_index()
        
        # Category-based statistics
        category_stats = df.groupby('category').agg({
            'id': 'count',
            'resolution_time_hours': 'mean'
        }).rename(columns={
            'id': 'category_total_faults',
            'resolution_time_hours': 'category_avg_resolution'
        }).reset_index()
        
        return df, location_stats, category_stats, location_daily
    
    def prepare_training_data(self, df, location_stats, category_stats):
        """Prepare training data with engineered features"""
        if df.empty:
            return None, None, None, None
        
        # Merge with statistics
        df = df.merge(location_stats, on='location_clean', how='left')
        df = df.merge(category_stats, on='category', how='left')
        
        # Fill NaN values
        df = df.fillna(0)
        
        # Encode categorical variables
        df['location_encoded'] = self.location_encoder.fit_transform(df['location_clean'])
        df['category_encoded'] = self.category_encoder.fit_transform(df['category'])
        df['department_encoded'] = self.department_encoder.fit_transform(df['assigned_department'])
        
        # Select features for prediction
        feature_columns = [
            'month', 'day_of_week', 'hour', 'is_weekend', 'is_business_hours',
            'location_encoded', 'category_encoded', 'department_encoded',
            'total_faults', 'avg_resolution_time', 'high_priority_ratio',
            'category_total_faults', 'category_avg_resolution'
        ]
        
        X = df[feature_columns].copy()
        
        # Create target variables
        y_frequency = df['total_faults']  # For frequency prediction
        y_category = df['category_encoded']  # For category prediction
        y_resolution = df['resolution_time_hours']  # For resolution time prediction
        
        return X, y_frequency, y_category, y_resolution
    
    def train_models(self, X, y_frequency, y_category, y_resolution):
        """Train prediction models"""
        if X is None or len(X) < 10:
            print("Insufficient data for training models")
            return False
        
        try:
            # Train frequency prediction model
            X_freq_train, X_freq_test, y_freq_train, y_freq_test = train_test_split(
                X, y_frequency, test_size=0.2, random_state=42
            )
            
            self.frequency_model = RandomForestRegressor(n_estimators=100, random_state=42)
            self.frequency_model.fit(X_freq_train, y_freq_train)
            
            # Train category prediction model
            X_cat_train, X_cat_test, y_cat_train, y_cat_test = train_test_split(
                X, y_category, test_size=0.2, random_state=42
            )
            
            self.category_model = RandomForestClassifier(n_estimators=100, random_state=42)
            self.category_model.fit(X_cat_train, y_cat_train)
            
            # Train resolution time prediction model
            X_res_train, X_res_test, y_res_train, y_res_test = train_test_split(
                X, y_resolution, test_size=0.2, random_state=42
            )
            
            self.location_model = RandomForestRegressor(n_estimators=100, random_state=42)
            self.location_model.fit(X_res_train, y_res_train)
            
            print("Models trained successfully")
            return True
            
        except Exception as e:
            print(f"Error training models: {e}")
            return False
    
    def predict_fault_likelihood(self, location, time_features=None):
        """Predict fault likelihood for a specific location and time"""
        if self.frequency_model is None:
            return None
        
        if time_features is None:
            now = datetime.now()
            time_features = {
                'month': now.month,
                'day_of_week': now.weekday(),
                'hour': now.hour,
                'is_weekend': 1 if now.weekday() >= 5 else 0,
                'is_business_hours': 1 if 8 <= now.hour <= 17 else 0
            }
        
        # Prepare features (using default values for new locations)
        features = np.array([[
            time_features['month'],
            time_features['day_of_week'],
            time_features['hour'],
            time_features['is_weekend'],
            time_features['is_business_hours'],
            0,  # location_encoded (default)
            0,  # category_encoded (default)
            0,  # department_encoded (default)
            5,  # total_faults (default)
            24, # avg_resolution_time (default)
            0.2, # high_priority_ratio (default)
            10, # category_total_faults (default)
            20  # category_avg_resolution (default)
        ]])
        
        try:
            likelihood = self.frequency_model.predict(features)[0]
            return max(0, min(100, likelihood * 10))  # Scale to 0-100%
        except Exception as e:
            print(f"Error predicting fault likelihood: {e}")
            return None
    
    def get_high_risk_locations(self, top_n=10):
        """Get locations with highest fault prediction risk"""
        conn = self.get_database_connection()
        
        # Get unique locations
        query = """
        SELECT DISTINCT location, COUNT(*) as historical_count
        FROM fault_reports 
        GROUP BY location
        ORDER BY historical_count DESC
        LIMIT %s
        """
        
        locations_df = pd.read_sql_query(query, conn, params=[top_n])
        conn.close()
        
        predictions = []
        for _, row in locations_df.iterrows():
            location = row['location']
            likelihood = self.predict_fault_likelihood(location)
            
            if likelihood is not None:
                predictions.append({
                    'location': location,
                    'risk_score': likelihood,
                    'historical_count': row['historical_count'],
                    'risk_level': 'High' if likelihood > 70 else 'Medium' if likelihood > 40 else 'Low'
                })
        
        return sorted(predictions, key=lambda x: x['risk_score'], reverse=True)
    
    def get_seasonal_patterns(self):
        """Analyze seasonal patterns in fault reporting"""
        conn = self.get_database_connection()
        
        query = """
        SELECT 
            EXTRACT(MONTH FROM created_at) as month,
            EXTRACT(DOW FROM created_at) as day_of_week,
            EXTRACT(HOUR FROM created_at) as hour,
            category,
            COUNT(*) as fault_count
        FROM fault_reports
        GROUP BY EXTRACT(MONTH FROM created_at), EXTRACT(DOW FROM created_at), 
                 EXTRACT(HOUR FROM created_at), category
        ORDER BY fault_count DESC
        """
        
        df = pd.read_sql_query(query, conn)
        conn.close()
        
        if df.empty:
            return {}
        
        # Monthly patterns
        monthly = df.groupby('month')['fault_count'].sum().to_dict()
        
        # Daily patterns
        daily = df.groupby('day_of_week')['fault_count'].sum().to_dict()
        
        # Hourly patterns
        hourly = df.groupby('hour')['fault_count'].sum().to_dict()
        
        # Category patterns
        category_monthly = df.groupby(['month', 'category'])['fault_count'].sum().unstack(fill_value=0).to_dict()
        
        return {
            'monthly': monthly,
            'daily': daily,
            'hourly': hourly,
            'category_monthly': category_monthly
        }
    
    def generate_predictions_report(self):
        """Generate comprehensive predictions report"""
        # Extract and prepare data
        data = self.extract_features_from_data()
        if not data or len(data) < 4:
            return {
                'error': 'Insufficient data for predictions',
                'recommendations': ['Collect more fault report data before generating predictions']
            }
        
        df, location_stats, category_stats, location_daily = data
        
        # Prepare training data
        X, y_frequency, y_category, y_resolution = self.prepare_training_data(df, location_stats, category_stats)
        
        # Train models
        if X is not None and len(X) > 10:
            self.train_models(X, y_frequency, y_category, y_resolution)
        
        # Get predictions
        high_risk_locations = self.get_high_risk_locations()
        seasonal_patterns = self.get_seasonal_patterns()
        
        # Generate recommendations
        recommendations = []
        if high_risk_locations:
            top_risk = high_risk_locations[0]
            recommendations.append(f"Prioritize monitoring {top_risk['location']} - highest risk location")
        
        if seasonal_patterns.get('monthly'):
            peak_month = max(seasonal_patterns['monthly'].items(), key=lambda x: x[1])
            recommendations.append(f"Increase resources during month {int(peak_month[0])} (peak fault period)")
        
        if seasonal_patterns.get('hourly'):
            peak_hour = max(seasonal_patterns['hourly'].items(), key=lambda x: x[1])
            recommendations.append(f"Most faults occur at {int(peak_hour[0])}:00 - optimize staff scheduling")
        
        return {
            'high_risk_locations': high_risk_locations,
            'seasonal_patterns': seasonal_patterns,
            'recommendations': recommendations,
            'total_faults_analyzed': len(df) if not df.empty else 0,
            'prediction_accuracy': 'Models trained successfully' if self.frequency_model else 'Insufficient data for training',
            'last_updated': datetime.now().isoformat()
        }

def main():
    """Main function to run fault prediction"""
    predictor = FaultPredictor()
    report = predictor.generate_predictions_report()
    
    # Save report to file
    with open('/tmp/fault_predictions.json', 'w') as f:
        json.dump(report, f, indent=2, default=str)
    
    print("Fault prediction report generated successfully")
    print(f"Total faults analyzed: {report.get('total_faults_analyzed', 0)}")
    print(f"High risk locations: {len(report.get('high_risk_locations', []))}")
    
    return report

if __name__ == "__main__":
    main()