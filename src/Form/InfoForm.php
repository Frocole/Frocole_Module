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
        $ro = false;

        $sid = $_GET['sid'];

        $data = $this->FetchInfo($sid);

        $request = \Drupal::request();
        if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
            $title = isset($_GET['id']) ? 'Edit Additional App Info' : 'Add Additional App Info';
            $route->setDefault('_title', $title);
        }

        // See https://api.drupal.org/api/drupal/elements/8.2.x

        $text = (isset($data['infotext'])) ? $data['infotext'] : '';

        $text = str_replace("\\r", "\r", $text);
        $text = str_replace("\\n", "\n", $text);

        // Find all segments and their id's.
        $form['SegmentID'] = [
            '#type' => 'select',
            '#title' => $this->t('Select Segment'),
            '#options' => $this->FetchSegments(),
            '#wrapper_attributes' => ['class' => 'col-md-6 col-xs-12'],
            '#disabled' => isset($sid),
            '#default_value' => (isset($sid)) ? $this->FetchSegmentName($sid) : '',
            ];

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
            //'infoid' => $form_state->getValue('SegmentID'),
            'infotext' => $text,
            'SegmentID' => $form_state->getValue('SegmentID')['SegmentID'],
        );

        // Fetch existing record (if any) to see if we need to insert or update.
        $orgdata = $this->FetchInfo($form_state->getValue('SegmentID')['SegmentID']);

        // See https://api.drupal.org/api/drupal/elements/8.2.x

        if (isset($orgdata)) {
            // update data in database
            Database::getConnection('default', 'frocole')
                ->update('infotexten')
                ->fields($data)
                ->condition('infoid', $orgdata['infoid'])
                ->execute();
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully edited an Additional Info with ID %infoid.', [ '%infoid' => $data['infoid']]));
        } else {
            // insert data to database
            Database::getConnection('default', 'frocole')
                ->insert('infotexten')
                ->fields($data)
                ->execute();
            \Drupal::messenger()
                ->addMessage($this->t('Succesfully added a new Additional Info.', [ ]));
        }

        $url = new Url('frocole.display_segments');
        $response = new RedirectResponse($url->toString());
        $response->send();
    }

    /**
     * @return an associated array of segments, their names.
     */
    private function FetchSegments()
    {
        // [segments]
        $query = Database::getConnection('default', 'frocole')
            ->select('segments', 's')
            ->fields('s', ['SegmentID', 'SegmentName']);

        $data = $query
            ->execute()
            ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

        $result = array();
        foreach ($data as $record) {
            // Do something with each $record
            $result[$record['SegmentID']] = $record['SegmentName'];
        }

        return $result;
    }

    private function FetchSegmentName($sid)
    {
        // [segments]
        $query = Database::getConnection('default', 'frocole')
            ->select('segments', 's')
            ->fields('s');

        $data = $query
            ->execute()
            ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

        foreach ($data as $record) {
            if ($record['SegmentID']==$sid) {
              return $record;
            }
        }

        return null;
    }

    private function FetchInfo($sid)
    {
        // [segments]
        $query = Database::getConnection('default', 'frocole')
            ->select('infotexten', 'i')
            ->fields('i');

        $data = $query
            ->execute()
            ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

        foreach ($data as $record) {
            if ($record['SegmentID']==$sid) {
              return $record;
            }
        }

        return null;
    }

    private function InsertInfo($sid, $infotext) {
        $data = array(
            'SegmentID' => $sid,
            'infotext' => $infotext,
        );

        // insert data to database
        Database::getConnection('default', 'frocole')
            ->insert('infotexten')
            ->fields($data)
            ->execute();
    }
}
