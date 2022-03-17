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

            // 'view' => t('View'),
            'delete' => t('Delete'),
            'edit' => t('Edit'),
            'info' => t('Info'),
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
            $url_edit   = Url::fromRoute('frocole.add_segment_form', ['id' => $data->SegmentID], []);
            $url_info   = Url::fromRoute('frocole.add_info_form', ['sid' => $data->SegmentID], []);

            //$linkView = Link::fromTextAndUrl(t('View'), $url_view);
            $linkDelete = Link::fromTextAndUrl(t('Delete'), $url_delete);
            $linkEdit   = Link::fromTextAndUrl(t('Edit'), $url_edit);
            $linkInfo   = Link::fromTextAndUrl(t('Info'), $url_info);

            //get data
            $rows[] = array(
                'SegmentID' => $data->SegmentID,
                'SegmentName' => (empty($data->SegmentName)?"<empty>":$data->SegmentName),

                //'view' => $linkView,
                // 'view'   => "View",
                'delete' => $linkDelete,
                'edit'   =>  $linkEdit,
                'info'   => $linkInfo,
            );
        }

        $form['links'] = [
          '#type' => 'item',
          '#markup' =>
          '<a href="'.Url::fromRoute('frocole.display_courses')->toString().'">'.t('Manage Courses').'</a> | ' .
          t('Manage Segments').' | '.
          '<a href="'.Url::fromRoute('frocole.display_infos')->toString().'">'.t('Manage Additional Infos').'</a> | ' .
          '<a href="'.Url::fromRoute('frocole.add_segment_form')->toString().'">'.t('Add a new Segment').'</a>',
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
