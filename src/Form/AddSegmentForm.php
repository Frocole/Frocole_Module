<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AddSegmentForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'add_segment_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $title = isset($_GET['id']) ? 'Edit Segment' : 'Add Segment';
            $route->setDefault('_title', $title);
        }

        $url = Url::fromRoute('frocole.display_segments');

        $form['add'] = [
        '#type' => 'item',
        '#markup' => '<a href="'.$url->toString().'">'.t('Manage Segments').'</a>',
        ];

        $conn = Database::getConnection('default', 'frocole');
        $data = array();
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
        '#required' => true,
        '#size' => 60,
        '#default_value' => (isset($data['SegmentName'])) ? $data['SegmentName'] : '',
        '#maxlength' => 20,
        '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12']
        ];

        $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('save'),
        '#buttom_type' => 'primary'
        ];

        return $form;
    }

    /**
     * @param array              $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
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
            $form_state->setErrorByName('Segments', $this->t('%msg: The Segment already existst.', [
                '%msg' => $this->t('Error'),
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $data = array(
            'SegmentName' => $form_state->getValue('SegmentName'),
        );

        if (isset($_GET['id'])) {
            // update data in database
            Database::getConnection('default', 'frocole')
                ->update('segments')
                ->fields($data)
                ->condition('SegmentID', $_GET['id'])
                ->execute();
        } else {
            // insert data to database
            Database::getConnection('default', 'frocole')
                ->insert('segments')
                ->fields($data)
                ->execute();
        }

        // show message and redirect to list page
        if (isset($_GET['id'])) {
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully edited an existing Segment with ID %id.', [ '%id' => $_GET['id']]));
        } else {
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully added a new Segment.', [ ]));
        }

        $url = new Url('frocole.display_segments');
        $response = new RedirectResponse($url->toString());
        $response->send();
    }
 }
