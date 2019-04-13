<?php

$app->get('/', function ( $request,  $response, array $args) {
    $response = $this->view->render($response, 'home.php');
    return $response;
});

$app->get('/login', function ( $request,  $response) use ($app){
    //  echo "Login page";
      $response =  $this->view->render($response, 'login.php');
      return $response;
});

$app->get('/calendar/events', function ($request, $response, $args) {
    // $queryParams = $request->getQueryParams();
    // $cwid = (int)$queryParams['cwid'];
    // $role = $queryParams['role'];
    $user = (array)$request->getAttribute('user');
    $cwid = $user['cwid'];
    $role = $user['role'];

    if ($role == 'admin') {
        return getAllEvents($this->db, $response);
    }   

     $query = "";
     
     if ($role == "student") {
         $query = "select es.EventScheduleId, es.Title EventTitle, es.Type, es.EventStart, es.EventEnd, es.Note1, es.Note2,
                   concat(c.Prefix, ' ', c.Num) Course, c.Title CourseTitle, c.Color, sec.crn, loc.RoomNo as Room 
                   from EventSchedule es
                   join StudentSection ssec on ssec.Section = es.SectionId
                   left join Section sec on sec.SectionId = es.SectionId
                   left join Course c on c.CourseId = sec.CourseId 
                   left join Room loc on loc.RoomId = es.LocationId
                   where ssec.CWID = :cwid";
     }
    else if ($role == "leadInstructor") {
        $query = "select temp.GroupId, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course, temp.CourseTitle,
                 temp.Color, GROUP_CONCAT(CRN SEPARATOR ', ') crn, temp.Room
                FROM 
                (
                select es.GroupId, es.Title EventTitle, es.Type, es.EventStart, es.EventEnd, es.Note1, es.Note2,
                    concat(c.Prefix, ' ', c.Num) Course, c.Title CourseTitle, c.Color,  convert(sec.CRN, char) CRN, loc.RoomNo as Room 

                    from EventSchedule es
                    left join Section sec on sec.SectionId = es.SectionId
                    left join Course c on c.CourseId = sec.CourseId 
                    left join Room loc on loc.RoomId = es.LocationId
                    where sec.LeadInstructor = :cwid
                ) temp
                group by temp.GroupId, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course, temp.CourseTitle,
                temp.Color, temp.Room";
    } else {
        $query = "select es.EventScheduleId, es.Title EventTitle, es.Type, es.EventStart, es.EventEnd, es.Note1, es.Note2,
                    concat(c.Prefix, ' ', c.Num) Course, c.Title CourseTitle, c.Color, sec.crn, loc.RoomNo as Room 
                    from EventSchedule es
                    join InstructorSection isec on isec.SectionId = es.SectionId
                    left join Section sec on sec.SectionId = es.SectionId
                    left join Course c on c.CourseId = sec.CourseId 
                    left join Room loc on loc.RoomId = es.LocationId
                    where isec.CWID = :cwid";
    }

    
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute(array("cwid"=>$cwid));
        $result = $stmt->fetchAll();
        $response = $response->withJson($result, 200);
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }

    //return $response->withRedirect('/new-url', 301);
    return $response;
});

$app->get('/users', function($request, $response){
    // $User = new User($this->db);
    $query = "select * from Users";
    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $response = $response->withJson($result);
    return $response;
});

$app->delete('/calendar/events', function($request, $response){
    $queryParams = $request->getQueryParams();
    $id = (int)$queryParams['id'];

    $query = "delete from EventSchedule where GroupId = $id";
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $response = $response->withJson(["status"=>"ok"], 200);
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }
    return $response;
});


$app->get('/calendar/event', function($request, $response){
    $queryParams = $request->getQueryParams();
    $id = (int)$queryParams['id'];
    
    $query = "select temp.GroupId as Id, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course
                , GROUP_CONCAT(CRN SEPARATOR ', ') CRN, temp.Room, temp.LocationId as RoomId
                FROM 
                (
                select es.GroupId, es.Title EventTitle, es.Type, es.EventStart, es.EventEnd, es.Note1, es.Note2,
                concat(c.Prefix, ' ', c.Num) Course, convert(sec.CRN, char) CRN, loc.RoomNo as Room, es.LocationId

                from EventSchedule es
                left join Section sec on sec.SectionId = es.SectionId
                left join Course c on c.CourseId = sec.CourseId 
                left join Room loc on loc.RoomId = es.LocationId
                where (es.GroupId = $id) or (es.EventScheduleId = $id)
                ) temp
                group by temp.GroupId, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course,
                temp.Room, temp.LocationId";

    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $response = $response->withJson($result);
    return $response;
});




$app->get('/calendar/create-event/course-section', function($request, $response){
    $user = (array)$request->getAttribute('user');
    $cwid = $user['cwid'];
    $role = $user['role'];

    $query = "select c.CourseId, concat(c.Prefix, ' ', c.Num) Course, sec.CRN, sec.SectionId from Course c join Section sec on sec.CourseId = c.CourseId"; 

    if ($role != "admin") {
        $query = $query . " where sec.LeadInstructor = $cwid";
    } 

    $stmt = $this->db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $courses = array();
    $idToCourse = array();
    $idToSection = array();

    foreach($result as $row) {
        
        if (!array_key_exists($row['Course'], $courses)) {
            $courses[$row['Course']] = [];
        }
        array_push($courses[$row['Course']], $row['CRN']);

        if (!array_key_exists($row["Course"], $idToCourse)){
            $idToCourse[$row["Course"]] = $row['CourseId'];
        }

        if (!array_key_exists($row["CRN"], $idToSection)){
            $idToSection[$row["CRN"]] = $row['SectionId'];
        }
    }
    $response = $response->withJson([$courses, $idToCourse, $idToSection], 200);
    return $response;
});


function getAllEvents($db, $response) {
    $query = "select temp.GroupId, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course, temp.CourseTitle,
                temp.Color, GROUP_CONCAT(CRN SEPARATOR ', ') crn, temp.Room
            FROM 
            (
            select es.GroupId, es.Title EventTitle, es.Type, es.EventStart, es.EventEnd, es.Note1, es.Note2,
                concat(c.Prefix, ' ', c.Num) Course, c.Title CourseTitle, c.Color,  convert(sec.CRN, char) CRN, loc.RoomNo as Room 

                from EventSchedule es
                left join Section sec on sec.SectionId = es.SectionId
                left join Course c on c.CourseId = sec.CourseId 
                left join Room loc on loc.RoomId = es.LocationId
            ) temp
            group by temp.GroupId, temp.EventTitle, temp.Type, temp.EventStart, temp.EventEnd, temp.Note1, temp.Note2, temp.Course, temp.CourseTitle,
            temp.Color, temp.Room";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();   
        $response = $response->withJson($result, 200);
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }

    //return $response->withRedirect('/new-url', 301);
    return $response;
    
}


