<?php

use \Firebase\JWT\JWT;



$app->post('/login', function($request, $response, array $args) {
    $parsedBody = $request->getParsedBody();
    $query = "select u.CWID, u.UserName, r.Role, p.Permissions from User u 
                join Role r on r.RoleId = u.RoleId 
                join RolePermission rp on rp.RoleId = r.RoleId
                join Permission p on p.PermissionId = rp.PermissionId
                where (u.UserName=:username or u.CWID=:username) and u.Password=:password";
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(
            "username" => $parsedBody["username"],
            "password" => $parsedBody["password"]
        ));
        $result = $stmt->fetchAll();
    
        if (!empty($result)){

            $user = array(
                'cwid'=> $result[0]['CWID'],
                'username'=>$result[0]['UserName'],
                'role'=>$result[0]['Role']
            );
    
            $permissions = array();
            foreach ($result as $row) {
                $permissions[] = $row['Permissions'];
            }
    
            $user['permissions'] = $permissions;

            //generate JWT token
            $payload = [
                'user'=> $user,
                'authenticated'=>"true",
                'loginTime'=>time(),
                'expires'=>'60'
            ];

            $key = "secretkeyyoushouldneveruse";
            $token = JWT::encode($payload, $key);
            $response = $response->withJson(['token'=> $token], 200);

        } else {
            $response = $response->withJson(["status"=>"ERROR", "message"=>"Incorrect Login Information"], 401);
        }
        
    } catch (PDOException $ex) {
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }
    return $response;
     
});

$app->post('/calendar/events', function ($request, $response, array $args) {

    $parsedBody = $request->getParsedBody();
    $query = "insert into EventSchedule(Title, Type, CourseId, SectionId, LocationId, Status, GroupId, EventStart, EventEnd, Note1, Note2, Created, CreatedBy) values ";

    foreach($parsedBody as $event) {
        $title = $event['title'];
        $type = $event['type'];
        $courseId = $event['courseId'];
        $sectionId = $event['sectionId'];
        $locationId = $event['locationId'];
        $status = $event['status'];
        $groupId = $event['groupId'];
        $eventStart = $event['eventStart'];
        $eventEnd = $event['eventEnd'];
        $note1 = $event['note1'];
        $note2 = $event['note2'];
        $created = $event['created'];
        $createdBy = $event['createdBy'];
        $query = $query . " ('$title', '$type', $courseId, $sectionId, $locationId, '$status', $groupId, '$eventStart', '$eventEnd', '$note1', '$note2', '$created', $createdBy),";
    }
    $query = rtrim($query,',');
    echo $query;
    // die();
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute();   
        $response = $response->withJson(["status"=>"ok"], 200);
        
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }

    //return $response->withRedirect('/new-url', 301);
    return $response;
});



$app->post('/calendar/create-event/available-rooms', function ($request, $response, array $args) {
    $parsedBody = $request->getParsedBody();
    $query = buildRoomQuery($parsedBody);
    try {
        $stmt = $this->db->prepare($query);
        $stmt->execute();   
        $result = $stmt->fetchAll();
        $response = $response->withJson($result, 200);
        
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }
    return $response;
});



function buildRoomQuery($parsedBody) {
    $innerQuery = "select es.EventScheduleId, es.LocationId, es.CourseId, es.SectionId, es.EventStart, es.EventEnd
                    from EventSchedule es where ";

    foreach($parsedBody as $daterange)
    {
        $start = date($daterange["start"]);
        $end = date($daterange["end"]);
        $innerQuery = $innerQuery . 
                   "((TIMESTAMPDIFF(MINUTE, es.EventStart, '$start') > 0
                        and TIMESTAMPDIFF(MINUTE,'$start' , es.EventEnd) > 0 
                    )
                    or (
                        TIMESTAMPDIFF(MINUTE, es.EventStart, '$end' ) > 0
                        and TIMESTAMPDIFF(MINUTE, '$end' , es.EventEnd) > 0 
                    )) OR ";
       
    }
    $innerQuery = $innerQuery . 'false';

    $fullQuery = "select distinct r.RoomId, concat(c.Prefix, ' ', c.Num) Course, sec.CRN, ins.CWID, ins.UserName as Owner
                    , case 
                        when temp.EventScheduleId is null then 'Available' 
                        else 'Booked' 
                    end AS Status
                    , concat(convert(temp.EventStart, char), ' - ', convert(temp.EventEnd, char)) as ConflictTimeRange
                    , temp.EventScheduleId ConflictEvent
                    , r.RoomNo
                    , r.Capacity
                    , case when r.Beds = 0 then 'No' else 'Yes' end as Beds
                    , case when r.Equipments = 0 then 'No' else 'Yes' end as Equipments
                    , case when r.Computer = 0 then 'No' else 'Yes' end as Computers
                    , case when r.AV = 0 then 'No' else 'Yes' end as AV
                    , r.Description 
                    from  Room r 
                    left join ( " . $innerQuery . 
                             " ) temp on temp.LocationId = r.RoomId
                    left join Course c on c.CourseId = temp.CourseId
                    left join Section sec on sec.SectionId = temp.SectionId
                    left join Instructor ins on ins.CWID = sec.LeadInstructor";

    return $fullQuery;
}


//update event 

/* 
try {
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(
            "title" => $parsedBody["title"],
            "type" => $parsedBody["type"],
            "status" => $parsedBody["status"],
            "course" => $parsedBody["course"],
            "CRN" => $parsedBody["CRN"],
            "semester" => (int)$parsedBody["semester"],
            "instructor" => $parsedBody["instructor"],
            "location" => $parsedBody["location"],
            "attendees" => (int)$parsedBody["attendees"],
            "eventStart" => $parsedBody["eventStart"],
            "eventEnd" => $parsedBody["eventEnd"],
            "notes" => $parsedBody["notes"],
            "createdBy" => "admin"
        ));   
        $response = $response->withJson(["status"=>"ok"], 200);
        
    } catch (PDOException $ex){
        $error = $ex->getMessage();
        $response = $response->withJson(array("error"=>$error), 500);
    }
*/