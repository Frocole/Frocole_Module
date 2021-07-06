<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;


/**
 * Class DeleteForm
 *
 * @package Drupal\frocole\Form
 */
class DeleteForm extends ConfirmFormBase
{

    public $id;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'delete_form';
    }

    public function getQuestion()
    {
        return t('Delete data');
    }

    public function getCancelUrl()
    {
        return new Url('frocole.display_data');
    }

    public function getDescription()
    {
        return t('Do you want to delete data number %id ?', array('%id' => $this->id));
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
        $query = Database::getConnection('default', 'frocole');
        $query->delete('courses')
            ->condition('CourseID', $this->id)
            ->execute();
        \Drupal::messenger()->addStatus('Succesfully deleted.');
        $form_state->setRedirect('frocole.display_data');
    }
}
