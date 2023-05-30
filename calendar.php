<?php
function build_calendar($month, $year, $resourceid) {
    $mysqli = new mysqli('127.0.0.1', 'root', '', 'bookingcalendar');

    // Create array containing abbreviations of days of week.
    $daysOfWeek = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

    // What is the first day of the month in question?
    $firstDayOfMonth = mktime(0,0,0, $month, 1, $year); // returns the Unix timestamp of the arguments given.

    // Retrieve some information about the first day of the month in question.
    $numberDays = date('t', $firstDayOfMonth);

    // What is the name of the month in question?
    $dateComponents = getdate($firstDayOfMonth);

    // Create the table tag opener and day headers.
    $monthName = $dateComponents['month'];
    $dayOfWeek = $dateComponents['wday'];
    $dateToday = date('Y-m-d');
    
    $datetoday = date('Y-m-d');
    $calendar = "<table class='table table-bordered'>";
    $calendar .= "<center><h2>$monthName $year</h2>";
    $calendar.= " <button class='changemonth btn btn-xs btn-primary' data-month='".date('m', mktime(0, 0, 0, $month-1, 1, $year))."' data-year='".date('Y', mktime(0, 0, 0, $month-1, 1, $year))."'>Previous Month</button>";

    $calendar.= " <button class='changemonth btn btn-xs btn-primary' id='current_month' data-month='".date('m')."' data-year='".date('Y')."'>Current Month</button>";

    $calendar.= " <button class='changemonth btn btn-xs btn-primary' data-month='".date('m', mktime(0, 0, 0, $month+1, 1, $year))."' data-year='".date('Y', mktime(0, 0, 0, $month+1, 1, $year))."'>Next Month</button></center><br>";

    $calendar.= "<label>Select Resource</label><select id='resource_select' class='form-control'>";
    $stmt = $mysqli->prepare("select * from resources");
      if($stmt->execute()){
          $result = $stmt->get_result();
          if($result->num_rows>0){
              while($row = $result->fetch_assoc()){
                $selected = $resourceid==$row['id'] ? 'selected' : '';
                $calendar.= "<option $selected value='{$row['id']}'>{$row['name']}</option>";
              }
          }
      }

    $calendar.= "</select><br>";

    $calendar.= "<tr>";

    foreach($daysOfWeek as $day){
        $calendar.= "<th class='header'>$day</th>";
    }

    $calendar.= "</tr><tr>";
    $currentDay = 1;
    if($dayOfWeek > 0) {
        for($k = 0; $k < $dayOfWeek; $k++) {
            $calendar.= "<td class='empty'></td>";
        }
    }

    $month = str_pad($month, 2, "0", STR_PAD_LEFT);

    while($currentDay <= $numberDays) {
        if($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $calendar.= "</tr><tr>";
        }

        $currentDayRel = str_pad($currentDay, 2, "0", STR_PAD_LEFT);
        $date = "$year-$month-$currentDayRel";
        $dayname = strtolower(date('l', strtotime($date)));
        $today = $date==date('Y-m-d')?'today': "";
        if($dayname=='saturday' || $dayname=='sunday'){
            $calendar.="<td><h4>$currentDay</h4> <button class='btn btn-secondary btn-xs'>Weekend</button>";
        } elseif ($date < date('Y-m-d')){
            $calendar.="<td><h4>$currentDay</h4> <button class='btn btn-danger btn-xs'>N/A</button>";
        } else {

            // 'Already Booked' if all slots are booked.
            // totalbookings == 2 (Depends on how many slots you have per day.)
            $totalbookings = checkSlots($mysqli, $date, $resourceid);
            if($totalbookings == 2) {
                $calendar.="<td class='$today'><h4>$currentDay</h4> <a href='#' class='btn btn-danger btn-xs'>Fully Booked</a></td>";
            } else {
                $availableslots = 2 - $totalbookings;
                $calendar.="<td class='$today'><h4>$currentDay</h4> <a href='book.php?date=".$date."&resource_id=".$resourceid."' class='btn btn-success btn-xs'>Book</a><small><i> $availableslots slots avail</i></small></td>";
            }
        }
    
        $currentDay++;
        $dayOfWeek++;
    }

    if($dayOfWeek < 7) {
        $remainingDays = 7 - $dayOfWeek;
        for($i = 0; $i < $remainingDays; $i++) {
            $calendar.= "<td class='empty'></td>";
        }
    }

    $calendar.= "</tr></table>";

    echo $calendar;

}

function checkSlots($mysqli, $date, $resourceid) {
    $stmt = $mysqli->prepare("select * from bookings where date = ? AND resource_id=?");
    $stmt->bind_param('si', $date, $resourceid);
    $totalbookings = 0;
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows>0){
            while($row = $result->fetch_assoc()){
                $totalbookings++;
            }

            $stmt->close();
        }
    }

    return $totalbookings;
}

$dateComponents = getdate();
if(isset($_POST['month']) && isset($_POST['year'])) {
$month = $_POST['month'];
$year = $_POST['year'];
$resourceid = $_POST['resource_id'];
}else{
$month = $dateComponents['mon'];
$year = $dateComponents['year'];
$resourceid = 1;
}
echo build_calendar($month,$year,$resourceid);

?>