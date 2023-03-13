<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A Form for Adding/Editing a Info Text.
 *
 * @category Form
 * @package Drupal\frocole\Form
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class AddInfoForm extends FormBase {

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The form id.
   */
  public function getFormId() {
    return 'add_info_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   *
   * @return Form
   *   The form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ro = FALSE;

    $data = [];
    if (isset($_GET['sid'])) {
        $data = $this->fetchInfo($_GET['sid']);
    }

    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = isset($_GET['sid']) ? 'Edit Additional App Info' : 'Add Additional App Info';
      $route->setDefault('_title', $title);
    }

    // See https://api.drupal.org/api/drupal/elements/8.2.x
    $text = (isset($data['infotext'])) ? $data['infotext'] : '';

    $text = str_replace("\\r", "\r", $text);
    $text = str_replace("\\n", "\n", $text);

    $url = Url::fromRoute('frocole.display_infos');

    $form['links'] = [
      '#type' => 'item',
      '#markup' =>
      '<a href="' . Url::fromRoute('frocole.display_courses')->toString() . '">' . t('Manage Courses') . '</a> | ' .
      '<a href="' . Url::fromRoute('frocole.display_segments')->toString() . '">' . t('Manage Segments') . '</a> | ' .
      '<a href="' . $url->toString() . '">' . t('Manage Additional Info') . '</a> | ' ,
    ];

    // Find all segments and their id's.
    $segid = (isset($data['SegmentID'])
      ? $data['SegmentID']
      : (isset($_GET['sid'])
        ? $_GET['sid']
        : ''));

    $form['SegmentID'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Segment'),
      '#options' => $this->fetchSegments(),
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
      '#disabled' => isset($segid) && $segid!='',
      '#default_value' => ($segid) ? $this->fetchSegmentName($segid) : '',
    ];

    $form['info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additionele App Info'),
      '#required' => TRUE,
      '#size' => 60,
      '#default_value' => $text,
      '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
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
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $info = $form_state->getValue('info');

    $conn = Database::getConnection('default', 'frocole');

    // Check for illegal characters.
    //
    // Warning: some mismatches between escape methods.
    //
    // mysqli_real_escape_string():
    //
    // NUL (ASCII 0),
    // \n,
    // \r,
    // \,
    // ',
    // ", and
    // Control-Z.
    //
    // Html::escape():
    //
    // & (ampersand),
    // " (double quote),
    // ' (single quote),
    // < (less than),
    // > (greater than).
    //
    if (str_contains($info, "<") && str_contains($info, ">")) {
      $form_state->setErrorByName('info', $this->t('%msg: The input contains &lt; and &gt; characters.', [
        '%msg' => $this->t('Error'),
      ]));
    }

    /*
    if ($info != Html::escape(trim($info,'/'))) {
    $form_state->setErrorByName('info',
    $this->t('%msg: The input contains illegal characters.', [
    '%msg' => $this->t('Error'),
    ]));
    }
     */
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form State.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $text = $form_state->getValue('info');
    // $text = str_replace("\r", "\\r", $text);
    // $text = str_replace("\n", "\\n", $text);

    $segid = is_array($form_state->getValue('SegmentID'))
      ? $form_state->getValue('SegmentID')['SegmentID']
      : $form_state->getValue('SegmentID');

    $data = [
          // 'infoid' => $form_state->getValue('SegmentID'),
      'infotext' => $text,
      'SegmentID' => $segid,
    ];

    // Fetch existing record (if any) to see if we need to insert or update.
    $orgdata = $this->fetchInfo($segid);

    // See https://api.drupal.org/api/drupal/elements/8.2.x
    if (isset($orgdata)) {
      // Update data in database.
      Database::getConnection('default', 'frocole')
        ->update('infotexten')
        ->fields($data)
        ->condition('infoid', $orgdata['infoid'])
        ->execute();
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully edited an Additional Info with ID %infoid.', ['%infoid' => $orgdata['infoid']]));
    }
    else {
      // Insert data to database.
      Database::getConnection('default', 'frocole')
        ->insert('infotexten')
        ->fields($data)
        ->execute();
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully added a new Additional Info.', []));
    }

    $url = new Url('frocole.display_infos');
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

  /**
   * Fetch the Segments.
   *
   * @return array
   *   An associated array of segments, their names.
   */
  private function fetchSegments() {
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

  /**
   * Fetch a Segment Name.
   *
   * @param int $sid
   *   The ID of the Segment.
   *
   * @return string
   *   The Segment name.
   */
  private function fetchSegmentName($sid) {
    // [segments]
    $query = Database::getConnection('default', 'frocole')
      ->select('segments', 's')
      ->fields('s');

    $data = $query
      ->execute()
      ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

    foreach ($data as $record) {
      if ($record['SegmentID'] == $sid) {
        return $record;
      }
    }

    return NULL;
  }

  /**
   * Fetch the Info Text.
   *
   * @param int $sid
   *   The ID of the Info Text.
   *
   * @return string
   *   The Info Text name.
   */
  private function fetchInfo($sid) {
    // [segments]
    $query = Database::getConnection('default', 'frocole')
      ->select('infotexten', 'i')
      ->fields('i');

    $data = $query
      ->execute()
      ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

    foreach ($data as $record) {
      if ($record['SegmentID'] == $sid) {
        return $record;
      }
    }

    return NULL;
  }

  /**
   * Insert Info Text.
   *
   * @param int $sid
   *   The Info Text ID.
   * @param string $infotext
   *   The Info Text.
   */
  private function insertInfo($sid, $infotext) {
    $data = [
      'SegmentID' => $sid,
      'infotext' => $infotext,
    ];

    // Insert data to database.
    Database::getConnection('default', 'frocole')
      ->insert('infotexten')
      ->fields($data)
      ->execute();
  }
}