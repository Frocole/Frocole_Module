<?php

namespace Drupal\frocole\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InfoForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'info_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        /*
        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $title = 'Edit App Info';
            $route->setDefault('_title', $title);
        }
        */

        $url = Url::fromRoute('frocole.display_courses');

        $conn = Database::getConnection('default', 'frocole');
        $data = array();
        $query = $conn
            ->select('infotexten', 'i')
            ->fields('i');
        $data = $query->execute()->fetchAssoc();

        // See https://api.drupal.org/api/drupal/elements/8.2.x

        $text = (isset($data['infotext'])) ? $data['infotext'] : '';

        $text = str_replace("\\r", "\r", $text);
        $text = str_replace("\\n", "\n", $text);

        $form['info'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Additionele App Info'),
          //'#description' => $this->t('Enter 3..10 performance indicator labels, separated by a formard slash (/).'),
          '#required' => true,
          '#size' => 60,
          '#default_value' => $text,
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
        $info=$form_state->getValue('info');

        $conn = Database::getConnection('default', 'frocole');

        // Check for illegal characters.
        //
        // Warning: some mismatches between escape methods.
        //
        // mysqli_real_escape_string(): NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.
        // Html::escape():              & (ampersand), " (double quote), ' (single quote), < (less than), > (greater than).

        if (str_contains($info, "<") && str_contains($info, ">")) {
            $form_state->setErrorByName('info', $this->t('%msg: The input contains &lt; and &gt; characters.', [
                '%msg' => $this->t('Error'),
            ]));
        }

        /*
        if ($info != Html::escape(trim($info,'/'))) {
            $form_state->setErrorByName('info', $this->t('%msg: The input contains illegal characters.', [
                '%msg' => $this->t('Error'),
            ]));
        }
        */
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $text = $form_state->getValue('info');
        //$text = str_replace("\r", "\\r", $text);
        //$text = str_replace("\n", "\\n", $text);

        $data = array(
            'infoid' => 1,
            'infotext' => $text,
        );

        // Fetch existing record (if any) to see if we need to insert or update.
        $conn = Database::getConnection('default', 'frocole');
        $orgdata = array();
        $query = $conn
            ->select('infotexten', 'i')
            ->fields('i');
        $orgdata = $query->execute()->fetchAssoc();

        // See https://api.drupal.org/api/drupal/elements/8.2.x

        if (isset($orgdata['infotext'])) {
            // update data in database
            Database::getConnection('default', 'frocole')
                ->update('infotexten')
                ->fields($data)
                ->execute();
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully edited an App Info with ID %infoid.', [ '%infoid' => $data['infoid']]));
        } else {
            // insert data to database
            Database::getConnection('default', 'frocole')
                ->insert('infotexten')
                ->fields($data)
                ->execute();
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully added a new App Info.', [ ]));
        }

        $url = new Url('frocole.display_courses');
        $response = new RedirectResponse($url->toString());
        $response->send();
    }
}
