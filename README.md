canvas-client-php
=================

A Canvas LMS API client for PHP. Requires Zend Framework >= v1.12
Very incomplete. 


Configuration & Setup
---
Files should go in your application's library folder, alongside the Zend folder. 
	
	myapp/
	    application/
	    library/
	        Zend/
	        OCAD/
	    
This package assumes you're using the [Zend Autoloader](http://framework.zend.com/manual/1.12/en/learning.autoloading.usage.html). 
You will need to add the OCAD namespace to your autoloader: 

	$autoloader = Zend_Loader_Autoloader::getInstance();
	$autoloader->registerNamespace('OCAD_');
	// Or, in application.ini: 
	autoloaderNamespaces.ocad = "OCAD_"


In your bootstrap, do:

	OCAD_Canvas_Client::$apiConfig = array(
		'defaultAccount' = 1,
		'url' 		 = "https://my.canvas.com/",
		'apiKey' 	 = "{my api key}",
	);
(This could also go in your application.ini config, and added as a bootstrap resource)

Usage
---
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
