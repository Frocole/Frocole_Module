<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class DisplayInfoTableController
 *
 * @package Drupal\frocole\Controller
 */
class DisplayInfoTableController extends ControllerBase
{
    public function index()
    {
        // create table header
        $header_table = array(
            'ID' => t('ID'),
            'SegmentID' => t('Segment ID'),
            'Segment Name' => t('Segment Name'),

            'Additional Info' => t('Additional Info'),

            // 'view' => t('View'),
            'delete' => t('Delete'),
            'edit' => t('Edit'),
        );

        // get data from database
        $query = Database::getConnection('default', 'frocole')
            ->select('infotexten', 'i');
        $query
            ->fields('i')
            ->orderBy('i.infoid','ASC');

        $results = $query->execute()->fetchAll();

        // build table
        $rows = array();
        foreach ($results as $data) {
            // $url_view    = Url::fromRoute('frocole.view_info_form', ['id' => $data->SegmentID], []);
            $url_delete  = Url::fromRoute('frocole.delete_info_form', ['id' => $data->infoid], []);
            $url_edit = Url::fromRoute('frocole.add_info_form', ['sid' => $data->SegmentID], []);

            // $linkView   = Link::fromTextAndUrl(t('View'), $url_view);
            $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
            $linkEdit= Link::fromTextAndUrl(t('Edit'), $url_edit);

            //Fetch Segment Name from SegmentID.
            $sn = $this->FetchSegmentName($data->SegmentID);

            //get data
            $rows[] = array(
                'ID' => $data->infoid,
                'SegmentID' => $data->SegmentID,
                'SegmentName' => (empty($sn)?"<empty>":$sn),

                'Text' => $data->infotext,

                // 'view' => $linkView,
                'delete' => $linkDelete,
                'edit' =>  $linkEdit,
            );
        }

        $form['links'] = [
            '#type' => 'item',
            '#markup' =>
            '<a href="'.Url::fromRoute('frocole.display_courses')->toString().'">'.t('Manage Courses').'</a> | ' .
            '<a href="'.Url::fromRoute('frocole.display_segments')->toString().'">'.t('Manage Segments').'</a> | ' .
            t('Manage Additional Info') . ' | ' .
            '<a href="'.Url::fromRoute('frocole.add_info_form')->toString().'">'.t('Add a new Additional Info').'</a>',
          ];

        // render table
        $form['table'] = [
          '#type' => 'table',
          '#header' => $header_table,
          '#rows' => $rows,
          '#empty' => t('No data found'),
        ];

        return $form;
    }

    private function FetchSegmentName($sid)
    {
        // [segments]
        $query = Database::getConnection('default', 'frocole')
            ->select('segments', 's')
            ->fields('s', ['SegmentID', 'SegmentName']);

        $data = $query
            ->execute()
            ->fetchAllAssoc('SegmentID', \PDO::FETCH_ASSOC);

        foreach ($data as $record) {
            if ($record['SegmentID']==$sid) {
              return $record['SegmentName'];
            }
        }

        return "";
    }
}
