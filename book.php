<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'bookingcalendar');
if(isset($_GET['date'])){

  $resourceid = $_GET['resource_id'];
    $stmt = $mysqli->prepare("select * from resources where id = ?");
    $stmt->bind_param('i', $resourceid);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows>0){
      $row = $result->fetch_assoc();
      $resourcename = $row['name'];
    }

  $date = $_GET['date'];
  $stmt = $mysqli->prepare("select * from bookings where date = ? AND resource_id = ?");
  $stmt->bind_param('si', $date, $resourceid);
  $bookings = array();
  if($stmt->execute()){
      $result = $stmt->get_result();
      if($result->num_rows>0){
          while($row = $result->fetch_assoc()){
              $bookings[] = $row['timeslot'];
          }
      }
  }
}

if(isset($_POST['submit'])){
  $name = $_POST['name'];
  $email = $_POST['email'];
  $timeslot = $_POST['timeslot'];
  $stmt = $mysqli->prepare("SELECT * FROM bookings where date = ? AND timeslot = ? AND resource_id = ?");
  $stmt->bind_param('ssi', $date, $timeslot, $resourceid);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($result->num_rows>0){
          $msg = "<div class='alert alert-danger'>Already Booked</div>";
        } else {
          $stmt = $mysqli->prepare("INSERT INTO bookings (name, timeslot, email, date, resource_id) VALUES (?,?,?,?,?)");
          $stmt->bind_param('ssssi', $name, $timeslot, $email, $date, $resourceid);
          $stmt->execute();
          $msg = "<div class='alert alert-success'>Booking Successfull</div>";
          $bookings[]=$timeslot;
          $stmt->close();
          $mysqli->close();
        }
    }

}

$duration = 240;
$cleanup = 0;
$start = "09:00";
$end = "17:00";

function timeslots($duration, $cleanup, $start, $end) {
  $start = new DateTime($start);
  $end = new DateTime($end);
  $interval = new DateInterval("PT".$duration."M");
  $cleanupInterval = new DateInterval("PT".$cleanup."M");
  $slots = array();

  for($intStart = $start; $intStart<$end; $intStart->add($interval)->add($cleanupInterval)) {
    $endPeriod = clone $intStart;
    $endPeriod->add($interval);
    if($endPeriod>$end) {
      break;
    }

    $slots[] = $intStart->format("H:iA")."-". $endPeriod->format("H:iA");
  }

  return $slots;

}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, inital-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    
    <title>Booking Page</title>

    <!-- Bootstrap -->

  </head>
  
  <body>
    <div class="container">
        <h1 class="text-center">Booking for resource "<?php echo $resourcename; ?>" Date: <?php echo date('d/m/Y', strtotime($date)); ?></h1><hr>
        <div class="row">
          <div class="col-md-12">
            <?php echo isset($msg)?$msg:""; ?>
          </div>
          <?php $timeslots = timeslots($duration, $cleanup, $start, $end);
          
          foreach($timeslots as $ts){          
          ?>
          <div class="col-md-2">
            <div class="form-group">
              <?php if(in_array($ts, $bookings)) { ?>
                <button class="btn btn-danger"><?php echo $ts;?></button>
              <?php } else { ?>
                <button class="btn btn-success book" data-timeslot="<?php echo $ts; ?>"><?php echo $ts;?></button>
              <?php } ?>  
            </div>
          </div>
          <?php } ?>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
      <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Booking: <span id="slot"></span></h4>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <form action="" method="post">
                  <div class="form-group">
                    <label for="">Timeslot</label>
                    <input required type="text" readonly name="timeslot" id="timeslot" class="form-control">
                  </div>
                  <div class="form-group">
                    <label for="">Name</label>
                    <input required type="text" name="name" id="timeslot" class="form-control">
                  </div>
                  <div class="form-group">
                    <label for="">Email</label>
                    <input required type="email" name="email" id="timeslot" class="form-control">
                  </div>
                  <div class="form-group" pull-right>
                    <button class="btn btn-primary" type="submit" name="submit">Submit</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>
  </body>
  <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script>
      $(".book").click(function(){
        var timeslot = $(this).attr('data-timeslot');
        $("#slot").html(timeslot);
        $("#timeslot").val(timeslot);
        $("#myModal").modal("show");
      })

    </script>
</html>