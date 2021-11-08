<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class DisplaySegmentTableController
 *
 * @package Drupal\frocole\Controller
 */
class DisplaySegmentTableController extends ControllerBase
{
    public function index()
    {
        // create table header
        $header_table = array(
            'SegmentID' => t('Segment ID'),
            'Segment Name' => t('Segment Name'),

            'view' => t('View'),
            'delete' => t('Delete'),
            'edit' => t('Edit'),
        );

        // get data from database
        $query = Database::getConnection('default', 'frocole')
            ->select('segments', 's');
        $query
            ->fields('s')
            ->orderBy('s.SegmentName','ASC');

        $results = $query->execute()->fetchAll();

        $rows = array();
        foreach ($results as $data) {
            //$url_view = Url::fromRoute('frocole.show_course', ['id' => $data->SegmentID], []);
            $url_delete = Url::fromRoute('frocole.delete_segment_form', ['id' => $data->SegmentID], []);
            $url_edit = Url::fromRoute('frocole.add_segment_form', ['id' => $data->SegmentID], []);

            //$linkView = Link::fromTextAndUrl(t('View'), $url_view);
            $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
            $linkEdit = Link::fromTextAndUrl(t('Edit'), $url_edit);

            //get data
            $rows[] = array(
                'SegmentID' => $data->SegmentID,
                'SegmentName' => (empty($data->SegmentName)?"<empty>":$data->SegmentName),

                //'view' => $linkView,
                'view' => "View",
                'delete' => $linkDelete,
                'edit' =>  $linkEdit,
            );
        }

        $url = Url::fromRoute('frocole.add_segment_form');

        $form['add'] = [
          '#type' => 'item',
          '#markup' => '<a href="'.$url->toString().'">'.t('Add a new Segment').'</a>',
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
}
