<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;


/**
 * Class DeleteCourseForm
 *
 * @package Drupal\frocole\Form
 */
class DeleteCourseForm extends ConfirmFormBase
{
    public $id;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'delete_course_form';
    }

    public function getQuestion()
    {
        return t('Delete Course');
    }

    public function getCancelUrl()
    {
        return new Url('frocole.display_courses');
    }

    public function getDescription()
    {
        //! TODO Add text it includes groups and students (not teachers)?
        //
        return t('Do you want to delete Course with ID %id ?', array('%id' => $this->id));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return t('Delete it!');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelText()
    {
        return t('Cancel');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $id = null)
    {
        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $route->setDefault('_title', t('Delete Course'));
        }

        $this->id = $id;

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $connection = Database::getConnection('default', 'frocole');

        //! [teacher id's]
        $query = $connection
            ->select('courses', 'c');
        $query
            ->addField('c','LeraarUserID');
        $teachers = $query
            ->execute()
            ->fetchAll();

        $teacherIDs = array();
        foreach ($teachers as $teacher)
        {
            $teacherIDs[] = $teacher->LeraarUserID;
        }

        //! [groups]
        $query = $connection
            ->select('groups', 'g')
            ->condition('g.CourseID', $this->id)
            ->fields('g');
        $groups = $query
            ->execute()
            ->fetchAllAssoc('GroupID', \PDO::FETCH_ASSOC);

        $feedbackDel = 0;

        foreach ($groups as $group) {
            $groupID = $group['GroupID'];

            //! [feedbackitems]
            $feedbackDel += $connection
                ->delete('feedbackitems')
                ->condition('GroupID', $groupID)
                ->execute();

            //! [userandgrouprelations]
            $connection
                ->delete('userandgrouprelations')
                ->condition('GroupID', $groupID)
                ->execute();
        }

        //! [users]
        $query = $connection
            ->select('userandcourserelations', 'u')
            ->condition('u.CourseID', $this->id)
            ->fields('u');
        $relations = $query
            ->execute()
            ->fetchAllAssoc('UserID', \PDO::FETCH_ASSOC);

        $usersDel = 0;
        foreach ($relations as $relation) {
            $userID = $relation['UserID'];

            //! Extend this to all known teachers!
            //! If not, a course might not show up (if the teacher is deleted by another course deletion).
            //!
            if (!in_array($userID, $teacherIDs)) {
                $usersDel += $connection
                    ->delete('users')
                    ->condition('UserID', $userID)
                    ->execute();
            }
        }

        //! [groups]
        $groupsDel = $connection
            ->delete('groups')
            ->condition('CourseID', $this->id)
            ->execute();

        //! [userandcourserelations]
        $connection
            ->delete('userandcourserelations')
            ->condition('CourseID', $this->id)
            ->condition("UserID", $teacherID, '<>')
            ->execute();

        //! [courses]
        $connection
            ->delete('courses')
            ->condition('CourseID', $this->id)
            ->execute();

        // show message and redirect to list page
        \Drupal::messenger()
            ->addMessage($this->t('Succesfully deleted a Course with ID %id, deleted %fd Feedback Items, %gd Groups and %ud Users.', [ '%id' => $this->id, '%fd' => $feedbackDel, '%gd' => $groupsDel, '%ud' => $usersDel ]));

        $form_state->setRedirect('frocole.display_courses');
    }
}
