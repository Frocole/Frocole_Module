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
        $query = $conn->select('feedbackitems', 'f')
            ->condition('GroupID', $id)
            ->fields('f');
        $data = $query
            ->execute()
            ->fetchAllAssoc('FeedBackItemID', \PDO::FETCH_ASSOC);
       
        $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

        fputcsv(
            $csv, array(
            t('FeedBackItem ID'), 
            t('Timestamp'), 
            t('Group ID'), 
            t('FeedbackSuplier ID'), 
            t('Subject'), 
            t('Parameter'), 
            t('Score'))
        );

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

        // output the stream as a string.
        rewind($csv);
        $build = [
            '#markup' => "<pre>".stream_get_contents($csv)."</pre>",
        ];

        //close the stream
        fclose($csv);

        return $build;
    }
}
