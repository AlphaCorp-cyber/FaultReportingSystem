import psycopg2
import random
from datetime import datetime, timedelta
import os

def generate_sample_fault_data():
    """Generate sample fault data for testing predictions"""
    
    # Database connection
    conn = psycopg2.connect(
        host=os.getenv('PGHOST', 'localhost'),
        database=os.getenv('PGDATABASE', 'postgres'),
        user=os.getenv('PGUSER', 'postgres'),
        password=os.getenv('PGPASSWORD', ''),
        port=os.getenv('PGPORT', '5432')
    )
    
    cur = conn.cursor()
    
    # Sample locations in Redcliff
    locations = [
        'Rutendo High School Area',
        'Redcliff Town Center',
        'Torwood Shopping Center',
        'Rutendo Primary School',
        'Redcliff Stadium',
        'Torwood Industrial Area',
        'Redcliff Hospital',
        'Maglas Shopping Center',
        'Redcliff Golf Course',
        'Torwood Police Station',
        'Redcliff Post Office',
        'Maglas Primary School',
        'Redcliff Market',
        'Torwood Community Hall',
        'Redcliff Fire Station'
    ]
    
    categories = ['water', 'roads', 'electricity', 'streetlights', 'waste', 'parks', 'other']
    departments = ['water', 'roads', 'electricity', 'electricity', 'waste', 'parks', 'general']
    statuses = ['submitted', 'assigned', 'in_progress', 'resolved', 'closed']
    priorities = ['low', 'medium', 'high', 'urgent']
    
    # Get existing user IDs
    cur.execute("SELECT id FROM users WHERE role = 'resident' LIMIT 10")
    user_ids = [row[0] for row in cur.fetchall()]
    
    if not user_ids:
        print("No resident users found. Creating sample user...")
        # Create a sample user
        cur.execute("""
            INSERT INTO users (first_name, last_name, email, password, role, verification_status, created_at)
            VALUES ('Sample', 'Resident', 'sample@resident.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'resident', 'approved', NOW())
            RETURNING id
        """)
        user_ids = [cur.fetchone()[0]]
        conn.commit()
    
    # Generate sample fault reports for the last 6 months
    start_date = datetime.now() - timedelta(days=180)
    sample_data = []
    
    for i in range(50):  # Generate 50 sample faults
        # Random date in the last 6 months
        random_days = random.randint(0, 180)
        created_at = start_date + timedelta(days=random_days)
        
        # Add some time variation
        created_at += timedelta(
            hours=random.randint(0, 23),
            minutes=random.randint(0, 59)
        )
        
        # Random location
        location = random.choice(locations)
        
        # Random category
        category = random.choice(categories)
        department = departments[categories.index(category)]
        
        # Random priority (higher priority more likely for certain categories)
        if category in ['water', 'electricity']:
            priority = random.choices(priorities, weights=[10, 30, 40, 20])[0]
        else:
            priority = random.choices(priorities, weights=[40, 40, 15, 5])[0]
        
        # Random status (most should be resolved for older faults)
        if created_at < datetime.now() - timedelta(days=30):
            status = random.choices(statuses, weights=[5, 10, 15, 40, 30])[0]
        else:
            status = random.choices(statuses, weights=[20, 25, 30, 20, 5])[0]
        
        # Generate reference number
        ref_number = f"FR{created_at.year}{str(i+1000).zfill(4)}"
        
        # Sample titles and descriptions
        titles = {
            'water': ['Water pipe burst', 'No water supply', 'Water pressure low', 'Water contamination'],
            'roads': ['Pothole on road', 'Road surface damaged', 'Road marking faded', 'Road closure needed'],
            'electricity': ['Power outage', 'Electrical pole damaged', 'Power fluctuation', 'Electrical hazard'],
            'streetlights': ['Street light not working', 'Street light damaged', 'Street light flickering'],
            'waste': ['Garbage not collected', 'Illegal dumping', 'Overflowing bins', 'Waste disposal issue'],
            'parks': ['Park equipment damaged', 'Grass cutting needed', 'Park maintenance required'],
            'other': ['General maintenance', 'Municipal service issue', 'Infrastructure problem']
        }
        
        title = random.choice(titles.get(category, ['General issue']))
        description = f"Reported issue: {title} at {location}. Requires immediate attention."
        
        # Random coordinates (approximate Redcliff area)
        latitude = -19.0 + random.uniform(-0.01, 0.01)
        longitude = 29.8 + random.uniform(-0.01, 0.01)
        
        # Updated time (for resolved faults)
        updated_at = created_at + timedelta(
            hours=random.randint(1, 72)
        ) if status in ['resolved', 'closed'] else None
        
        sample_data.append({
            'reference_number': ref_number,
            'user_id': random.choice(user_ids),
            'category': category,
            'title': title,
            'description': description,
            'location': location,
            'latitude': latitude,
            'longitude': longitude,
            'priority': priority,
            'status': status,
            'assigned_department': department,
            'created_at': created_at,
            'updated_at': updated_at
        })
    
    # Insert sample data
    insert_query = """
    INSERT INTO fault_reports (
        reference_number, user_id, category, title, description, location,
        latitude, longitude, priority, status, assigned_department, created_at, updated_at
    ) VALUES (
        %(reference_number)s, %(user_id)s, %(category)s, %(title)s, %(description)s, %(location)s,
        %(latitude)s, %(longitude)s, %(priority)s, %(status)s, %(assigned_department)s, %(created_at)s, %(updated_at)s
    )
    """
    
    cur.executemany(insert_query, sample_data)
    conn.commit()
    
    print(f"Generated {len(sample_data)} sample fault reports")
    
    # Generate some progress updates for resolved faults
    cur.execute("SELECT id FROM fault_reports WHERE status IN ('resolved', 'closed') LIMIT 10")
    fault_ids = [row[0] for row in cur.fetchall()]
    
    progress_updates = []
    for fault_id in fault_ids:
        # Add some progress updates
        for status in ['assigned', 'in_progress', 'resolved']:
            progress_updates.append({
                'fault_id': fault_id,
                'status': status,
                'message': f"Fault status updated to {status}",
                'created_by': random.choice(user_ids),
                'is_visible_to_resident': True,
                'created_at': datetime.now() - timedelta(days=random.randint(1, 30))
            })
    
    if progress_updates:
        progress_query = """
        INSERT INTO fault_progress_updates (
            fault_id, status, message, created_by, is_visible_to_resident, created_at
        ) VALUES (
            %(fault_id)s, %(status)s, %(message)s, %(created_by)s, %(is_visible_to_resident)s, %(created_at)s
        )
        """
        cur.executemany(progress_query, progress_updates)
        conn.commit()
        print(f"Generated {len(progress_updates)} progress updates")
    
    cur.close()
    conn.close()
    
    print("Sample data generation completed successfully!")

if __name__ == "__main__":
    generate_sample_fault_data()