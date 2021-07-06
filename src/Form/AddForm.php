<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;


class AddForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
          $title = (isset($_GET['id'])) ? "Edit Course": "Add Course";
          $route->setDefault('_title', $title);
        }

        $url = Url::fromRoute('frocole.display_data');

        $form['index'] = [
        '#type' => 'item',
        '#markup' => '<a href="'.$url->toString().'">View All Courses</a>',
        ];

        $conn = Database::getConnection('default', 'frocole');
        $data = array();
        if (isset($_GET['id'])) {
            $query = $conn->select('courses', 'm')
                ->condition('CourseID', $_GET['id'])
                ->fields('m');
            $data = $query->execute()->fetchAssoc();
        }

        // See https://api.drupal.org/api/drupal/elements/8.2.x

        $form['CourseName'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Course Name'),
        '#required' => true,
        '#size' => 60,
        '#default_value' => (isset($data['CourseName'])) ? $data['CourseName'] : '',
        '#maxlength' => 128,
        '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12']
        ];
        $form['IPF_RD_parameters'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Individual Performance'),
        '#required' => true,
        '#size' => 60,
        '#default_value' => (isset($data['IPF_RD_parameters'])) ? $data['IPF_RD_parameters'] : '',
        '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12']
        ];
        $form['GPF_RD_parameters'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Group Performance'),
        '#required' => true,
        '#default_value' => (isset($data['GPF_RD_parameters'])) ? $data['GPF_RD_parameters'] : '',
        '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12']
        ];

        // Find all users and their id's.
        $form['LeraarUserID'] = [
        '#type' => 'select',
        '#title' => $this
        ->t('Select leraar'),
        '#options' => $this->FetchUsers(),
        '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
        '#default_value' => (isset($data['LeraarUserID'])) ? $data['LeraarUserID'] : '',
        ];

        $form['CourseActive'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('active'),
        '#required' => true,
        '#default_value' => (isset($data['CourseActive'])) ? $data['CourseActive'] : '',
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
        if (is_numeric($form_state->getValue('first_name'))) {
            $form_state->setErrorByName('first_name', $this->t('Error, The First Name Must Be A String'));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $data = array(
        'CourseName' => $form_state->getValue('CourseName'),
        'IPF_RD_parameters' => $form_state->getValue('IPF_RD_parameters'),
        'GPF_RD_parameters' => $form_state->getValue('GPF_RD_parameters'),
        'LeraarUserID' => $form_state->getValue('LeraarUserID'),
        'CourseActive' => $form_state->getValue('CourseActive'),
        );

        if (isset($_GET['id'])) {
            // update data in database
            Database::getConnection('default', 'frocole')->update('courses')->fields($data)->condition('CourseID', $_GET['id'])->execute();
        } else {
            // insert data to database
            Database::getConnection('default', 'frocole')->insert('courses')->fields($data)->execute();
        }

        // show message and redirect to list page
        \Drupal::messenger()->addStatus('Succesfully saved');
        $url = new Url('frocole.display_data');
        $response = new RedirectResponse($url->toString());
        $response->send();
    }

    /**
     * @return an associated array of user'is, their names and nicknames.
     */
    private function FetchUsers()
    {
        // [Users]
        $query = Database::getConnection('default', 'frocole')
            ->select('users', 'u')
            ->fields('u', ['UserID', 'Username', 'Nickname']);

        $data = $query
            ->execute()
            ->fetchAllAssoc('UserID', \PDO::FETCH_ASSOC);

        $result = array();
        foreach ($data as $record) {
            // Do something with each $record
            $result[$record['UserID']] = "[".str_pad($record['UserID'], 4, '0', STR_PAD_LEFT)."] ".$record['Username']." (".$record['Nickname'].")";
        }

        return $result;
    }
}
