<?php

namespace Drupal\mymodule\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ExportController
 *
 * @package Drupal\mymodule\Controller
 */
class ExportController extends ControllerBase
{
    public function export($id)
    {
        // To show the csv as content of a page:
        //
        // $conn = Database::getConnection('default', 'frocole');
        // $csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
        //
        //      Write csv header and data to the $csv stream with fputcsv().
        //
        // rewind($csv);
        //
        // Output the stream as a raw html PRE string for display on a page.
        // $build = [
        //    '#markup' => "<pre>".stream_get_contents($csv)."</pre>",
        // ];
        // fclose($csv);
        //
        // return $build;

        // Creating a dynamic csv for download using Symphony:
        //
        // See http://web.archive.org/web/20190915170056/http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
        //      
        $response = new StreamedResponse(
            function () use ($id) {
                $conn = Database::getConnection('default', 'frocole');

                // Reserve 5M memory for the in-memory file.
                $csv = fopen('php://output', 'r+');

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

                // Close the stream.
                fclose($csv);
            }
        );

        // download or show as text.
        //
        $download = true;
        
        $response->headers->set('Content-Type', $download ? 'text/csv' : 'text/plain');
        $response->headers->set('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename=' . 'frocole_group_'.$id . '.csv');
    
        return $response;
    }
}
