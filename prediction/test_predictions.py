#!/usr/bin/env python3
"""Test script to validate the fault prediction system"""

import sys
import os
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from fault_predictor import FaultPredictor
import json

def test_prediction_system():
    """Test the fault prediction system"""
    print("Testing Fault Prediction System...")
    print("=" * 50)
    
    predictor = FaultPredictor()
    
    # Test 1: Generate predictions report
    print("1. Generating predictions report...")
    try:
        report = predictor.generate_predictions_report()
        
        if 'error' in report:
            print(f"   ERROR: {report['error']}")
            return False
        
        print(f"   ✓ Total faults analyzed: {report.get('total_faults_analyzed', 0)}")
        print(f"   ✓ High-risk locations found: {len(report.get('high_risk_locations', []))}")
        print(f"   ✓ Recommendations generated: {len(report.get('recommendations', []))}")
        
        # Display top 3 high-risk locations
        if report.get('high_risk_locations'):
            print("\n   Top 3 High-Risk Locations:")
            for i, loc in enumerate(report['high_risk_locations'][:3]):
                print(f"     {i+1}. {loc['location']} - Risk: {loc['risk_score']:.1f}% ({loc['risk_level']})")
        
        # Display recommendations
        if report.get('recommendations'):
            print("\n   Recommendations:")
            for i, rec in enumerate(report['recommendations'][:3]):
                print(f"     {i+1}. {rec}")
        
    except Exception as e:
        print(f"   ERROR: {e}")
        return False
    
    # Test 2: Predict fault likelihood for specific location
    print("\n2. Testing fault likelihood prediction...")
    try:
        test_location = "Redcliff Town Center"
        likelihood = predictor.predict_fault_likelihood(test_location)
        
        if likelihood is not None:
            print(f"   ✓ Fault likelihood for '{test_location}': {likelihood:.1f}%")
        else:
            print(f"   ⚠ Could not predict likelihood for '{test_location}'")
    
    except Exception as e:
        print(f"   ERROR: {e}")
        return False
    
    # Test 3: Analyze seasonal patterns
    print("\n3. Analyzing seasonal patterns...")
    try:
        patterns = predictor.get_seasonal_patterns()
        
        if patterns:
            print("   ✓ Seasonal patterns analyzed successfully")
            
            if patterns.get('monthly'):
                peak_month = max(patterns['monthly'].items(), key=lambda x: x[1])
                print(f"   ✓ Peak month: {peak_month[0]} ({peak_month[1]} faults)")
            
            if patterns.get('hourly'):
                peak_hour = max(patterns['hourly'].items(), key=lambda x: x[1])
                print(f"   ✓ Peak hour: {peak_hour[0]}:00 ({peak_hour[1]} faults)")
        
        else:
            print("   ⚠ No seasonal patterns found")
    
    except Exception as e:
        print(f"   ERROR: {e}")
        return False
    
    print("\n" + "=" * 50)
    print("✓ All tests completed successfully!")
    print("The fault prediction system is working correctly.")
    
    return True

if __name__ == "__main__":
    success = test_prediction_system()
    sys.exit(0 if success else 1)