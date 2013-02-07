canvas-client-php
=================

A Canvas LMS API client for PHP. Requires Zend Framework >= v1.12
Very incomplete. 


Configuration 
-------------
OCAD_Canvas_Client::$apiConfig = array(
	'defaultAccount' = 1,
	'url' 		 = "https://my.canvas.com/",
	'apiKey' 	 = "{my api key}",
);


Usage
-----
$courseId = <canvas course id or "sis id">; 
$course = OCAD_Canvas_Course::load($courseId);
$course->getStudents();
$course->getGrades();
$course->publish();

$userId = <canvas user id or "sis id">;
$user = OCAD_Canvas_User::load($userId);
$user->getTodos();
$user->getActivityStream();
$user->getCourses();

$grade = $course->getGradeForStudent($user);
$grade->grade;
