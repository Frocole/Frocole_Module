<?php

namespace Drupal\frocole\Form;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * A Form for Delete a Info Text.
 *
 * @category Form
 * @package Drupal\frocole\Form
 * @author Wim van der Vegt <wim.vandervegt@ou.nl>
 * @license https://github.com/Frocole/Frocole_Module/blob/develop/LICENSE.MD GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * @link https://github.com/Frocole/Frocole_Module the Frocole Repository.
 */
class DeleteInfoForm extends ConfirmFormBase {
  public $id;

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Form ID.
   */
  public function getFormId() {
    return 'delete_info_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   the Question.
   */
  public function getQuestion() {
    return t('Delete Additional Info');
  }

  /**
   * {@inheritdoc}
   *
   * @return Url
   *   the Cancel Url.
   */
  public function getCancelUrl() {
    return new Url('frocole.display_infos');
  }

  /**
   * {@inheritdoc}
   *
   * @return String
   *   the Description.
   */
  public function getDescription() {
    return t('Do you want to delete Info with ID %id ?', ['%id' => $this->id]);
  }

  /**
   * {@inheritdoc}
   *
   * @return String
   *   the Confirm Text.
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   *
   * @return String
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
   * @param $id
   *   the ID of the Info Text to remove.
   *
   * @return Form
   *   The Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', t('Delete Additional Info'));
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
   *
   * @return Form
   *   the Validate Form
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
   *
   * @return Form
   *   the Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = Database::getConnection('default', 'frocole');

    $query
      ->delete('infotexten')
      ->condition('infoid', $this->id)
      ->execute();

    // Show message and redirect to list page.
    \Drupal::messenger()
      ->addMessage($this->t('Succesfully deleted a Info with ID %id.', ['%id' => $this->id]));

    $form_state->setRedirect('frocole.display_infos');
  }

}
