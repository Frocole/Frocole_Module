<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * A Form for Adding/Editing a Segment.
 *
 * @category Form
 * @package Drupal\frocole\Form
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class AddSegmentForm extends FormBase {

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The form id.
   */
  public function getFormId() {
    return 'add_segment_form';
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
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = isset($_GET['id']) ? 'Edit Segment' : 'Add Segment';
      $route->setDefault('_title', $title);
    }

    $url = Url::fromRoute('frocole.display_segments');

    $form['add'] = [
      '#type' => 'item',
      '#markup' => '<a href="' . $url->toString() . '">' . t('Manage Segments') . '</a>',
    ];

    $conn = Database::getConnection('default', 'frocole');
    $data = [];
    if (isset($_GET['id'])) {
      $query = $conn
        ->select('segments', 's')
        ->condition('SegmentID', $_GET['id'])
        ->fields('s');
      $data = $query->execute()->fetchAssoc();
    }

    // See https://api.drupal.org/api/drupal/elements/8.2.x
    $form['SegmentName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Segment Name'),
      '#required' => TRUE,
      '#size' => 60,
      '#default_value' => (isset($data['SegmentName'])) ? $data['SegmentName'] : '',
      '#maxlength' => 20,
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
    $conn = Database::getConnection('default', 'frocole');

    $name = $form_state->getValue('SegmentName');

    $query = Database::getConnection('default', 'frocole')
      ->select('segments', 's')
      ->fields('s')
      ->condition('SegmentName', $name, '=');

    $num_rows = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($num_rows != 0) {
      $form_state->setErrorByName(
            'Segments', $this->t(
                '%msg: The Segment already existst.', [
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'SegmentName' => $form_state->getValue('SegmentName'),
    ];

    if (isset($_GET['id'])) {
      // Update data in database.
      Database::getConnection('default', 'frocole')
        ->update('segments')
        ->fields($data)
        ->condition('SegmentID', $_GET['id'])
        ->execute();
    }
    else {
      // Insert data to database.
      Database::getConnection('default', 'frocole')
        ->insert('segments')
        ->fields($data)
        ->execute();
    }

    // Show message and redirect to list page.
    if (isset($_GET['id'])) {
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully edited an existing Segment with ID %id.', ['%id' => $_GET['id']]));
    }
    else {
      \Drupal::messenger()
        ->addMessage($this->t('Succesfully added a new Segment.', []));
    }

    $url = new Url('frocole.display_segments');
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

}
