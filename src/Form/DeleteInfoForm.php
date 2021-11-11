<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class DeleteInfoForm
 *
 * @package Drupal\frocole\Form
 */
class DeleteInfoForm extends ConfirmFormBase
{
    public $id;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'delete_info_form';
    }

    public function getQuestion()
    {
        return t('Delete Additional Info');
    }

    public function getCancelUrl()
    {
        return new Url('frocole.display_infos');
    }

    public function getDescription()
    {
        return t('Do you want to delete Info with ID %id ?', array('%id' => $this->id));
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
            $route->setDefault('_title', t('Delete Additional Info'));
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
        $query = Database::getConnection('default', 'frocole');

        $query
            ->delete('infotexten')
            ->condition('infoid', $this->id)
            ->execute();

        // show message and redirect to list page
        \Drupal::messenger()
            ->addMessage($this->t('Succesfully deleted a Info with ID %id.', [ '%id' => $this->id ]));

        $form_state->setRedirect('frocole.display_infos');
    }
}
