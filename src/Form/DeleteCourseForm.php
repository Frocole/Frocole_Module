<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * A Form for Delete a Course.
 *
 * @category Form
 * @package Drupal\frocole\Form
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class DeleteCourseForm extends ConfirmFormBase {

  /**
   * The Segment ID to delete.
   *
   * @var int
   *   the Segment ID to delete.
   */
  public $id;

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Form ID.
   */
  public function getFormId() {
    return 'delete_course_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Question.
   */
  public function getQuestion() {
    return t('Delete Course');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Url
   *   the Cancel Url.
   */
  public function getCancelUrl() {
    return new Url('frocole.display_courses');
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Description.
   */
  public function getDescription() {
    // ! TODO Add text it includes groups and students (not teachers)?
    //
    return t('Do you want to delete Course with ID %id ?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Confirm Text.
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Cancel Text.
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   the Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   the Form State.
   * @param int $id
   *   the ID of the Course to delete.
   *
   * @return Form
   *   The Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', t('Delete Course'));
    }

    $this->id = $id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   the Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   the Form State.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   the Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   the Form State.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $connection = Database::getConnection('default', 'frocole');

    // ! [teacher id's]
    $query = $connection
      ->select('courses', 'c');
    $query
      ->addField('c', 'LeraarUserID');
    $teachers = $query
      ->execute()
      ->fetchAll();

    $teacherIDs = [];
    foreach ($teachers as $teacher) {
      $teacherIDs[] = $teacher->LeraarUserID;
    }

    // ! [groups]
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

      // ! [feedbackitems]
      $feedbackDel += $connection
        ->delete('feedbackitems')
        ->condition('GroupID', $groupID)
        ->execute();

      // ! [userandgrouprelations]
      $connection
        ->delete('userandgrouprelations')
        ->condition('GroupID', $groupID)
        ->execute();
    }

    // ! [users]
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

      // ! Extend this to all known teachers!
      // ! If not, a course might not show up
      // ! (if the teacher is deleted by another course deletion).
      // !
      if (!in_array($userID, $teacherIDs)) {
        $usersDel += $connection
          ->delete('users')
          ->condition('UserID', $userID)
          ->execute();
      }
    }

    // ! [groups]
    $groupsDel = $connection
      ->delete('groups')
      ->condition('CourseID', $this->id)
      ->execute();

    // ! [userandcourserelations]
    $connection
      ->delete('userandcourserelations')
      ->condition('CourseID', $this->id)
      ->condition("UserID", $teacherID, '<>')
      ->execute();

    // ! [courses]
    $connection
      ->delete('courses')
      ->condition('CourseID', $this->id)
      ->execute();

    // Show message and redirect to list page.
    \Drupal::messenger()
      ->addMessage($this->t('Succesfully deleted a Course with ID %id, deleted %fd Feedback Items, %gd Groups and %ud Users.',
      ['%id' => $this->id, '%fd' => $feedbackDel, '%gd' => $groupsDel, '%ud' => $usersDel]));

    $form_state->setRedirect('frocole.display_courses');
  }

}
