<?php
namespace Topxia\Service\Classes\Impl;

use Topxia\Service\Classes\ClassesService;
use Topxia\Service\Common\BaseService;
use Topxia\Common\ArrayToolkit;

class ClassesServiceImpl extends BaseService implements ClassesService
{
    public function getClass($id)
    {
        return $this->getClassesDao()->getClass($id);
    }


    public function findClassesByIds(array $ids)
    {
        $classes=$this->getClassesDao()->findClassesByIds($ids);
        return ArrayToolkit::index($classes, 'id');
    }

    public function searchClasses($conditions, $sort = array(), $start, $limit) 
    {
        $conditions = array_filter($conditions);   
        return $this->getClassesDao()->searchClasses($conditions, $sort, $start, $limit);
    }

    public function searchClassCount($conditions)
    {
        return $this->getClassesDao()->searchClassCount($conditions);
    }

    public function createClass($class)
    {
        $class = $this->getClassesDao()->createClass($class);
        $this->addOrUpdateTeacher($class['headTeacherId'], $class['id'], 'HEAD_TEACHER');
/*        $classMember['classId'] = $class['id'];
        $classMember['userId'] = $class['headTeacherId'];
        $classMember['role'] = 'HEAD_TEACHER';
        $classMember['createdTime'] = time();
        $this->addClassMember($classMember);*/
        return $class;
    }

    public function getStudentClass($userId)
    {
        $member = $this->getClassMemberDao()->getMemberByUserId($userId);
        if (empty($member)) {
            return null;
        }

        return $this->getClass($member['classId']);
    }

    public function getClassHeadTeacher($classId)
    {
        $member = $this->getClassMemberDao()->getMemberByClassIdAndRole($classId, 'HEAD_TEACHER');
        if (empty($member)) {
            return null;
        }

        $user = $this->getUserService()->getUser($member['userId']);
        $profile = $this->getUserService()->getUserProfile($member['userId']);

        if (empty($user) or empty($profile)) {
            return null;
        }

        return array_merge($user, $profile);
    }

    public function getClassesByHeadTeacherId($headTeacherId)
    {
        return $this->getClassesDao()->findClassesByHeadTeacherId($headTeacherId);
    }

    public function checkPermission($name, $classId)
    {
        $class = $this->getClass($classId);
        if (empty($class)) {
            return false;
        }

        $user = $this->getCurrentUser();
        if (empty($user)) {
            return false;
        }


        $member = $this->getClassMemberDao()->getMemberByUserIdAndClassId($user['id'], $classId);
        if ($user->isAdmin()) {
            $member = array(
                'id' => null,
                'userId' => $user['id'],
                'classId' => $class['id'],
                'role' => 'ADMIN',
            );
        }

        if (empty($member)) {
            return false;
        }

        $permissionRoles = array(
            'view' => array('STUDENT', 'TEACHER', 'HEAD_TEACHER', 'ADMIN'),
            'manage' => array('HEAD_TEACHER', 'ADMIN'),
        );

        if (!array_key_exists($name, $permissionRoles)) {
            return false;
        }

        return in_array($member['role'], $permissionRoles[$name]);
    }

    public function canViewClass($classId)
    {
        $class = $this->getClass($classId);
        if (empty($class)) {
            return false;
        }

        $user = $this->getCurrentUser();
        if (empty($user)) {
            return false;
        }

        $member = $this->getClassMemberDao()->getMemberByUserIdAndClassId($user['id'], $classId);

        if ($user->isAdmin()) {
            $member = array(
                'id' => null,
                'userId' => $user['id'],
                'classId' => $class['id'],
                'role' => 'ADMIN',
            );
        } else {
            if (!in_array($member['role'], array('HEAD_TEACHER', 'STUDENT'))) {
                return false;
            }
        }

        return array($class, $member);
    }

    public function canManageClass($classId)
    {
        $class = $this->getClass($classId);
        if (empty($class)) {
            return false;
        }

        $user = $this->getCurrentUser();
        if (empty($user)) {
            return false;
        }

        $member = $this->getClassMemberDao()->getMemberByUserIdAndClassId($user['id'], $classId);

        if ($user->isAdmin()) {
            $member = array(
                'id' => null,
                'userId' => $user['id'],
                'classId' => $class['id'],
                'role' => 'ADMIN',
            );
        } else {
            if (!in_array($member['role'], array('HEAD_TEACHER'))) {
                return false;
            }
        }

        return array($class, $member);
    }

    public function canMemberManageClass($class, $member)
    {
        if (empty($class) or empty($member)) {
            return false;
        }

        if (!in_array($member['role'], array('HEAD_TEACHER', 'ADMIN'))) {
            return false;
        }

        return true;
    }

    public function editClass($fields, $id)
    {
        $class = $this->getClassesDao()->editClass($fields, $id);
        $this->addOrUpdateTeacher($class['headTeacherId'], $id, 'HEAD_TEACHER');
        return $class;
    }

    public function addOrUpdateTeacher($userId, $classId, $role)
    {
        $classDao = $this->getClassMemberDao();
        $classMember = $classDao->getMemberByUserIdAndClassId($userId, $classId);
        if(empty($classMember)) {
            $newClassMember = array();
            $newClassMember['classId'] = $classId;
            $newClassMember['userId'] = $userId;
            $newClassMember['role'] = $role;
            $newClassMember['createdTime'] = time();
            $classMember = $classDao->addClassMember($newClassMember);
        } else {
            if ($role != $classMember['role'] && $role == 'HEAD_TEACHER') {
                $this->updateClassMember(array('role'=>$role), $classMember['id']);
            }
        }

        return $classMember;
    }

    public function updateClassStudentNum($num,$id){
        $this->getClassesDao()->updateClassStudentNum($num,$id);
    }

    public function deleteClass($id)
    {
        return $this->getClassesDao()->deleteClass($id);
    }

    public function getMemberByUserIdAndClassId($userId,$classId)
    {
        return $this->getClassMemberDao()->getMemberByUserIdAndClassId($userId, $classId);
    }

    public function getStudentMemberByUserIdAndClassId($userId ,$classId){
        return $this->getClassMemberDao()->getStudentMemberByUserIdAndClassId($userId, $classId);
    }

    public function findClassStudentMembers($classId)
    {
        return $this->getClassMemberDao()->findMembersByClassIdAndRole($classId, 'STUDENT');
    }

    public function findClassMemberByUserNumber($number, $classId)
    {
        $user = $this->getUserService()->getUserByNumber($number);
        if(empty($user)) {
            return null;
        } else {
            return $this->getClassMemberDao()->getStudentMemberByUserIdAndClassId($user['id'], $classId);
        }
    }

    public function findClassByUserNumber($number)
    {
        $user = $this->getUserService()->getUserByNumber($number);
        if(empty($user)) {
            return null;
        } else {
            return $this->getStudentClass($user['id']);
        }
    }

    public function findClassMembersByUserIds(array $userIds)
    {
        return $this->getClassMemberDao()->findClassMembersByUserIds($userIds);
    }

    public function searchClassMembers(array $conditions, array $oderBy, $start, $limit)
    {
        return $this->getClassMemberDao()->searchClassMembers($conditions, $oderBy, $start, $limit);
    }

    public function searchClassMemberCount(array $conditions)
    {
        return $this->getClassMemberDao()->searchClassMemberCount($conditions);
    }

    public function addClassMember(array $classMember){
        return $this->getClassMemberDao()->addClassMember($classMember);
    }

    public function deleteClassMemberByUserId($userId){
        $this->getClassMemberDao()->deleteClassMemberByUserId($userId);
    }

    public function updateClassMember(array $fields, $id)
    {
        return $this->getClassMemberDao()->updateClassMember($fields, $id);
    }

    public function importStudents($classId, array $userIds){
        foreach ($userIds as $userId) 
        {
            $classMember['classId']=$classId;
            $classMember['userId']=$userId;
            $classMember['role']='STUDENT';
            $classMember['title']='';
            $classMember['createdTime']=time();
            $this->addClassMember($classMember);
        }
        $this->updateClassStudentNum(count($userIds),$classId);
    }

    public function refreashStudentRank($userId, $classId)
    {
        $studentMembers = $this->findClassStudentMembers($classId);
        $studentIds = ArrayToolkit::column($studentMembers, 'userId');
        $students = $this->getUserService()->findUsersByIdsAndOrder($studentIds, array('point', 'DESC'));
        $student = array();
        $currentRank = 0;
        $index = 1;
        foreach ($students as $item) {
            if($item['id'] == $userId) {
                $student = $item;
                $currentRank = $index;
                break;
            }
            $index++;
        }

        $studentMember = $this->getMemberByUserIdAndClassId($userId, $classId);
        $newMember = array();
        $newMember['currentRank'] = $currentRank;
        $newMember['rate'] = empty($students) ? '0%' : ($currentRank == 1 ? '100%' : round( (count($students) - $currentRank)/count($students) * 100) . "％");
        if(time() > $studentMember['lastRankChangeTime'] + 86400 ) {
            $newMember['lastRank'] = $studentMember['currentRank'];
            $newMember['lastRankChangeTime'] = time();
        }

        return $this->updateClassMember($newMember, $studentMember['id']); 
    }
    private function getClassesDao()
    {
        return $this->createDao('Classes.ClassesDao');
    }

    private function getClassMemberDao()
    {
        return $this->createDao('Classes.ClassMemberDao');
    }

    private function getUserService()
    {
        return $this->createService('User.UserService');
    }
}