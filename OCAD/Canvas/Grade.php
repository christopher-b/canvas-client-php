<?php
class OCAD_Canvas_Grade extends OCAD_Canvas_Model
{
	public $grade;
	public $student;
	public $course;
	
	public function __construct($grade, OCAD_Canvas_User $student, OCAD_Canvas_Course $course)
	{
		$this->grade 	= $grade;
		$this->student 	= $student;
		$this->course 	= $course;
	}
}