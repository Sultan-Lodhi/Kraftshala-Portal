<?php
header('content-type: application/json; charset=utf-8');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
include 'config.php';
$rq = $_SERVER['REQUEST_METHOD'];
$params = file_get_contents('php://input');
$pVals = json_decode($params, true);

if ($rq == 'GET') // Get listing of currencies
{
    if ($_GET['flag'] == 1) { // flag for user login
        $name = $_GET['name'];
        $email = $_GET['email'];
        $sql = "SELECT user_type FROM kraftshala.registered_users WHERE name = '$name' AND email = '$email'";
        $sqlRes = getArrayFromSql2($sql);
        $data['userExists'] = count($sqlRes) == 1 ? true : false;
        $data['userType'] = $sqlRes[0]['user_type'];
        send_json_new($data, false, 'User Login Successfully', false, false);
    } elseif ($_GET['flag'] == 2) { // fetching upcoming submission
        $sql = "SELECT * FROM kraftshala.add_assignment ORDER BY deadline DESC";
        $data = getArrayFromSql2($sql);
        send_json_new($data, false, 'Upcoming Assignments listed Successfully', false, false);
    } elseif ($_GET['flag'] == 3) { // fetching submitted submissions
        $where = '';
        $user = $_GET['user'];
        $search = $_GET['search'];
        if ($user) { // fetch submitted documents of login user only
            $where = "WHERE sub.email = " . "'$user'";
        } elseif ($search) {
            $where = "WHERE sub.email LIKE " . "'%$search%'" . " OR sub.student_name LIKE" . "'%$search%'";
        }
        $sql = "SELECT * FROM kraftshala.submissions as sub INNER JOIN kraftshala.add_assignment as aa ON aa.id = sub.assignment_id $where ORDER BY submission_date DESC";
        $data = getArrayFromSql2($sql);
        send_json_new($data, false, 'Submissions listed Successfully', false, false);
    }
} elseif ($rq == 'POST') {
    if ($_POST['action'] == 1) { // Instructor add assignment
        $file = $_FILES['file'];
        $filePath = uploadFile($file); //uploading file to root directory
        // saving file and data into database
        $name = $_POST['name'];
        $subject = $_POST['subject'];
        $deadline = $_POST['deadline'];
        $sql = "INSERT INTO kraftshala.add_assignment SET name = '$name', subject = '$subject',file_path = '$filePath',deadline='$deadline'";
        runQuery_new($sql);
        send_json_new(1, false, 'Assignment added Successfully!', false, false);
    } elseif ($pVals['action'] == 2) { // user registration
        // print_r($pVals);
        $name = $pVals['name'];
        $email = $pVals['email'];
        $password = $pVals['password'];
        $userType = $pVals['userType'];
        $curDate = date("Y-m-d");
        if ($pVals['subject']) {
            $allSubjects = implode(', ', $pVals['subject']);
            $subject = ",subject = " . "'$allSubjects'";
        }
        // check whether user already registered or not
        $checkUser = "SELECT count(*) as cnt FROM kraftshala.registered_users WHERE email = '$email'";
        $checkUserCount = getArrayFromSql2($checkUser)[0]['cnt'];
        if ($checkUserCount == 0) { // New user
            $sql = "INSERT INTO kraftshala.registered_users SET name = '$name', email = '$email',password = '$password',user_type='$userType',creation_date='$curDate' $subject";
            runQuery_new($sql);
            send_json_new(1, false, 'Registration Done Successfully!', false, false);
        } else { // user already registered
            send_json_new(1, false, 'User Already Registered!', false, false);
        }
    } elseif ($_POST['action'] == 3) { // Student submits assignment
        $file = $_FILES['file'];
        $filePath = uploadFile($file); //uploading file to root directory
        $assignId = $_POST['assignment_id'];
        $studentName = $_POST['studentName'];
        $email = $_POST['email'];
        $submissionTime = date("Y-m-d");
        $assignmentDeadline = $_POST['assignment_deadline'];
        if ($submissionTime > $assignmentDeadline) { // checking submission time
            send_json_new(1, true, 'Deadline exceeded. Can not submit now!', false, false);
        }
        // check whether Student already submitted the assignment or not
        $checkUser = "SELECT count(*) as cnt FROM kraftshala.submissions WHERE assignment_id = '$assignId' and email = '$email'";
        $checkUserCount = getArrayFromSql2($checkUser)[0]['cnt'];
        if ($checkUserCount == 0) { // New submission
            $sql = "INSERT INTO kraftshala.submissions SET student_name = '$studentName', email = '$email',assignment_id = '$assignId',submission_date='$submissionTime',submitted_assignment = '$filePath'";
            runQuery_new($sql);
            send_json_new(1, false, 'Registration Done Successfully!', false, false);
        } else { // user already submitted assignment
            send_json_new(1, true, 'Student has already Submitted this assignment!', false, false);
        }
    } elseif ($pVals['action'] == 4) { // Submit grades
        $grade = $pVals['stGrade'];
        $assignId = $pVals['assignment_id'];
        $email = $pVals['email'];
        $sql = "UPDATE kraftshala.submissions SET grade = '$grade' WHERE assignment_id = '$assignId' and email = '$email'";
        runQuery_new($sql);
        $data['grade'] = $grade;
        send_json_new($data, false, 'Grades Submitted Successfully!', false, false);
    }

}

/******** Function to return sql data in assoc array *********/
function getArrayFromSql2($qry)
{
    $retAry = array();
    $res = runQuery_new($qry);
    while ($row = mysqli_fetch_assoc($res)) {
        $retAry[] = $row;
    }
    return $retAry;
}
/******** Function to run database queries *********/
function runQuery_new($query)
{
    global $sqlCon;
    $conn = $sqlCon;
    $res = $conn->query($query);
    return $res;
}

/******** Function to return data in json format *********/
function send_json_new($data, $error = false, $message = false, $error_code = false, $totalRec = false)
{
    $data = $data ? $data : array();
    $arr = array('data' => $data, 'error' => $error);
    if ($message) {
        $arr['message'] = $message;
    }
    if ($error_code) {
        $arr['error_code'] = $error_code;
    }
    $arr['recordsTotal'] = $totalRec ? $totalRec : 0;
    echo json_encode($arr);
    exit;
}
/******** Function to upload file *********/
function uploadFile($file)
{
    $filename = $file['name'];
    $type = explode("/", $file['type'])[1];
    if (($file['size'] / 1000000) > 10) { // File size <= 10 MB check
        send_json_new(1, true, 'File size should not exceed 10 MB', false, false);
    } elseif ($type != 'pdf' && $type != 'PDF') {
        send_json_new(1, true, 'File should be PDF Only', false, false);
    }
    $filename = $file['name'];
    $rootDir = realpath($_SERVER["DOCUMENT_ROOT"]) . '/';
    move_uploaded_file($file['tmp_name'], $rootDir . $filename);
    $filePath = $rootDir . $filename;
    return $filePath;
}
