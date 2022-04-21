<?php

namespace Drupal\frocole\Form;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A Form for Adding/Editing a Course.
 * 
 * @category Form
 * @package Drupal\frocole\Controller
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class AddCourseForm extends FormBase {

  /**
   * {@inheritdoc}
   *
   * @return String
   *   the form id.
   */
  public function getFormId() {
    return 'add_course_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   the form stage.
   *
   * @return the form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = isset($_GET['id']) ? 'Edit Course' : 'Add Course';
      $route->setDefault('_title', $title);
    }

    $url = Url::fromRoute('frocole.display_courses');

    $form['add'] = [
      '#type' => 'item',
      '#markup' => '<a href="' . $url->toString() . '">' . t('Manage Courses') . '</a>',
    ];

    $conn = Database::getConnection('default', 'frocole');
    $data = [];
    if (isset($_GET['id'])) {
      $query = $conn
        ->select('courses', 'm')
        ->condition('CourseID', $_GET['id'])
        ->fields('m');
      $data = $query->execute()->fetchAssoc();
    }

    // See https://api.drupal.org/api/drupal/elements/8.2.x
    $form['CourseName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course Name'),
      '#required' => TRUE,
      '#size' => 60,
      '#default_value' => (isset($data['CourseName'])) ? $data['CourseName'] : '',
      '#maxlength' => 20,
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
    ];
    $form['IPF_RD_parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Individual Performance'),
      '#description' => $this->t('Enter 3..10 performance indicator labels, separated by a formard slash (/).'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => (isset($data['IPF_RD_parameters'])) ? $data['IPF_RD_parameters'] : '',
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
    ];
    $form['GPF_RD_parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group Performance'),
      '#description' => $this->t('Enter 3..10 performance indicator labels, separated by a formard slash (/).'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => (isset($data['GPF_RD_parameters'])) ? $data['GPF_RD_parameters'] : '',
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
    ];

    // Find all users and their id's.
    $form['SegmentID'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Segment'),
      '#options' => $this->_fetchSegments(),
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
      '#default_value' => (isset($data['SegmentID'])) ? $data['SegmentID'] : '',
    ];

    // Find all users and their id's.
    $form['LeraarUserID'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Teacher'),
      '#options' => $this->fetchUsers(),
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
      '#default_value' => (isset($data['LeraarUserID'])) ? $data['LeraarUserID'] : '',
    ];

    $form['CourseActive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('active'),
      '#required' => FALSE,
      '#default_value' => (isset($data['CourseActive'])) ? $data['CourseActive'] : '',
      '#wrapper_attributes' => ['class' => ['col-md-6 col-xs-12']],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('save'),
      '#buttom_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Validates the form input.
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   *
   * @return the Validate Form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('IPF_RD_parameters');
    $gp = $form_state->getValue('GPF_RD_parameters');

    $conn = Database::getConnection('default', 'frocole');

    $ipf = count(explode('/', trim($ip, '/')));
    $gpf = count(explode('/', trim($gp, '/')));

    // Check Min/Max Number of indicators.
    if ($ipf < 3) {
      $form_state->setErrorByName(
            'IPF_RD_parameters', $this->t(
                '%msg: The minimum number of %ip performance %in is %no.', [
                  '%msg' => $this->t('Error'),
                  '%ip' => $this->t('individual'),
                  '%in' => $this->t('indicators'),
                  '%no' => 3,
                ]
            )
        );
    }
    else {
      if ($ipf > 10) {
        $form_state->setErrorByName(
              'IPF_RD_parameters', $this->t(
                  '%msg: The maximum number of %ip performance %in is %no.', [
                    '%msg' => $this->t('Error'),
                    '%ip' => $this->t('individual'),
                    '%in' => $this->t('indicators'),
                    '%no' => 10,
                  ]
              )
          );
      }
    }

    // Check Min/Max Number of indicators.
    if ($gpf < 3) {
      $form_state->setErrorByName(
            'GPF_RD_parameters', $this->t(
                '%msg: The minimum number of %ip performance %in is %no.', [
                  '%msg' => $this->t('Error'),
                  '%ip' => $this->t('group'),
                  '%in' => $this->t('indicators'),
                  '%no' => 3,
                ]
            )
        );
    }
    elseif ($gpf > 10) {
      $form_state->setErrorByName(
            'GPF_RD_parameters', $this->t(
                '%msg: The maximum number of %ip performance %in is %no.', [
                  '%msg' => $this->t('Error'),
                  '%ip' => $this->t('group'),
                  '%in' => $this->t('indicators'),
                  '%no' => 10,
                ]
            )
            );
    }

    // Check leading or trailing separators.
    if ($ip != trim($ip, '/')) {
      $form_state->setErrorByName(
            'IPF_RD_parameters', $this->t(
                '%msg: The input contains to many separators.', [
                  '%msg' => $this->t('Error'),
                ]
            )
        );
    }

    // Check leading or trailing separators.
    if ($gp != trim($gp, '/')) {
      $form_state->setErrorByName(
            'GPF_RD_parameters', $this->t(
                '%msg: The input contains to many separators.', [
                  '%msg' => $this->t('Error'),
                ]
            )
        );
    }

    // Check on empty indicators.
    if (in_array("", explode('/', trim($ip, '/')))) {
      $form_state->setErrorByName(
            'IPF_RD_parameters', $this->t(
                '%msg: The input contains empty %in.', [
                  '%msg' => $this->t('Error'),
                  '%in' => $this->t('indicators'),
                ]
            )
        );
    }

    // Check on empty indicators.
    if (in_array("", explode('/', trim($gp, '/')))) {
      $form_state->setErrorByName(
            'GPF_RD_parameters', $this->t(
                '%msg: The input contains empty %in.', [
                  '%msg' => $this->t('Error'),
                  '%in' => $this->t('indicators'),
                ]
            )
        );
    }

    // Check for illegal characters.
    //
    // Warning: some mismatches between escape methods.
    //
    // mysqli_real_escape_string(): NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.
    // Html::escape():              & (ampersand), " (double quote), ' (single quote), < (less than), > (greater than).
    if ($ip != Html::escape(trim($ip, '/'))) {
      $form_state->setErrorByName(
            'IPF_RD_parameters', $this->t(
                '%msg: The input contains illegal characters.', [
                  '%msg' => $this->t('Error'),
                ]
            )
        );
    }

    // Check for illegal characters.
    if ($gp != Html::escape(trim($gp, '/'))) {
      $form_state->setErrorByName(
            'GPF_RD_parameters', $this->t(
                '%msg: The input contains illegal characters.', [
                  '%msg' => $this->t('Error'),
                ]
            )
        );
    }

    // Check if Leraar has the same SegmentID as the Course.
    $sid = $form_state->getValue('SegmentID');
    $lid = $form_state->getValue('LeraarUserID');

    // SELECT u.UserID FROM `users` u WHERE u.UserID=28 AND u.SegmentID=4
    // [segments]m note we can only use the Users table and id's from the form as the course might not be inserted yet.
    // so check if the LeraarUser matches the Course Segment.
    $query = Database::getConnection('default', 'frocole')
      ->select('users', 'u')
      ->fields('u')
      ->condition('UserID', $lid, '=')
      ->condition('SegmentID', $sid, '=');

    $num_rows = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($num_rows == 0) {
      $form_state->setErrorByName(
            'Segments', $this->t(
                '%msg: The Leraar\'s segment does not match the Course\'s segment.', [
                  '%msg' => $this->t('Error'),
                ]
            )
        );
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   the Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   the Form State.
   *
   * @return the Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'CourseName' => $form_state->getValue('CourseName'),
      'IPF_RD_parameters' => $form_state->getValue('IPF_RD_parameters'),
      'GPF_RD_parameters' => $form_state->getValue('GPF_RD_parameters'),
      'SegmentID' => $form_state->getValue('SegmentID'),
      'LeraarUserID' => $form_state->getValue('LeraarUserID'),
      'CourseActive' => $form_state->getValue('CourseActive'),
    ];

    if (isset($_GET['id'])) {
      // Update data in database.
      Database::getConnection('default', 'frocole')
        ->update('courses')
        ->fields($data)
        ->condition('CourseID', $_GET['id'])
        ->execute();
    }
    else {
      // Insert data to database.
      Database::getConnection('default', 'frocole')
        ->insert('courses')
        ->fields($data)
        ->execute();
    }

    // Show message and redirect to list page.
    if (isset($_GET['id'])) {
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully edited an existing Course with ID %id.', ['%id' => $_GET['id']]));
    }
    else {
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully added a new Course.', []));
    }

    $url = new Url('frocole.display_courses');
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

  /**
   * Fetched the Users.
   *
   * @return an associated array of user's, their names and nicknames.
   */
  private function fetchUsers() {
    // [Users]
    $query = Database::getConnection('default', 'frocole')
      ->select('users', 'u')
      ->fields('u', ['UserID', 'Username', 'Nickname']);

    $data = $query
      ->execute()
      ->fetchAllAssoc('UserID', \PDO::FETCH_ASSOC);

    $result = [];
    foreach ($data as $record) {
      // Do something with each $record.
      $result[$record['UserID']] = "[" . str_pad($record['UserID'], 4, '0', STR_PAD_LEFT) . "] " . $record['Username'] . " (" . $record['Nickname'] . ")";
    }

    return $result;
  }

  /**
   * Fetches the Segments.
   *
   * @return an associated array of segments, their names.
   */
  private function _fetchSegments() {
    // [segments]
    $query = Database::getConnection('default', 'frocole')
      ->select('segments', 's')
      ->fields('s', ['SegmentID', 'SegmentName']);

    $data = $query
      ->execute()
      ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

    $result = [];
    foreach ($data as $record) {
      // Do something with each $record.
      $result[$record['SegmentID']] = $record['SegmentName'];
    }

    return $result;
  }

}
