<?php

namespace ApiBundle\Api\Resource\CourseSet;

use ApiBundle\Api\Resource\Course\CourseFilter;
use ApiBundle\Api\Resource\Filter;

class CourseSetCourseFilter extends Filter
{
    protected $publicFields = array();

    public function publicFields(&$data)
    {
        $courseFilter = new CourseFilter();
        $courseFilter->filter($data);
    }
}