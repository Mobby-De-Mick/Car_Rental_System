<?php
include "connect.php";

if (isset($_POST['Ctype'])) {
    $Ctype = mysqli_real_escape_string($connect, $_POST['Ctype']);
    $startDate = isset($_POST['start_date']) ? mysqli_real_escape_string($connect, $_POST['start_date']) : null;
    $days = isset($_POST['Days']) ? (int)$_POST['Days'] : 0;
    
    $query = "SELECT c.license_no, c.model 
              FROM cars c
              WHERE c.Ctype = '$Ctype' 
              AND c.is_available = 1";
    
    // Add availability check if start date is provided
    if ($startDate && $days > 0) {
        $endDate = date('Y-m-d', strtotime($startDate . " + $days days"));
        
        $query .= " AND c.license_no NOT IN (
                      SELECT r.license_no 
                      FROM rental r 
                      WHERE (
                          (r.Sdate <= '$startDate' AND DATE_ADD(r.Sdate, INTERVAL r.Nodays DAY) >= '$startDate') OR
                          (r.Sdate <= '$endDate' AND DATE_ADD(r.Sdate, INTERVAL r.Nodays DAY) >= '$endDate') OR
                          ('$startDate' <= r.Sdate AND '$endDate' >= r.Sdate)
                      )
                  )";
    }
    
    $result = mysqli_query($connect, $query);
    
    $options = '<option value="">Select Vehicle</option>';
    
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $options .= '<option value="' . $row['license_no'] . '">' . 
                        $row['model'] . ' (' . $row['license_no'] . ')' . 
                        '</option>';
        }
    } else {
        $options .= '<option value="">No available vehicles for selected type/period</option>';
    }
    
    echo $options;
}
?>