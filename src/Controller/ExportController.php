<?php

namespace Drupal\mymodule\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class ExportController
 *
 * @package Drupal\mymodule\Controller
 */
class ExportController extends ControllerBase
{
    public function export($id)
    {
        // See https://socalwebworks.com/blog/import-drupal-8-content-csv-file
        // See https://drupalpeople.com/blog/output-data-csv-file-drupal-download
        // See https://www.drupal.org/node/2181523

        $conn = Database::getConnection('default', 'frocole');

        $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

        // Get Fields.
        $fields = $conn
            ->query("DESCRIBE feedbackitems")
            ->fetchAll();
 
        $fieldnames = array();
        foreach ($fields as $field) {
            array_push($fieldnames, $field->Field);
        }

        fputcsv($csv, $fieldnames);

        // Get Data to Export.
        $query = $conn
            ->select('feedbackitems', 'f')
            ->condition('GroupID', $id)
            ->fields('f');
        $data = $query
            ->execute()
            ->fetchAllAssoc('FeedBackItemID', \PDO::FETCH_ASSOC);
            
        foreach ($data as $record) {
            fputcsv(
                $csv, array(    
                $record['FeedBackItemID'], 
                $record['Timestamp'], 
                $record['GroupID'], 
                $record['FeedbackSuplierID'], 
                $record['Subject'], 
                $record['Parameter'], 
                $record['Score'])
            );
        }

        // Output the stream as a raw html PRE string.
        rewind($csv);
        $build = [
            '#markup' => "<pre>".stream_get_contents($csv)."</pre>",
        ];

        // Close the stream
        fclose($csv);

        return $build;
    }
}
