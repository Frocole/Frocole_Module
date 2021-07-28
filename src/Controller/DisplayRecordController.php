<?php

namespace Drupal\frocole\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;

/**
 * Class DisplayRecordController
 *
 * @package Drupal\frocole\Controller
 */
class DisplayRecordController extends ControllerBase
{

    /**
     * @return array
     */
    public function show($id)
    {
        // see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Database%21Database.php/function/Database%3A%3AgetConnection/8.9.x
        // see https://api.drupal.org/api/drupal/core%21lib%21Drupal.php/function/Drupal%3A%3Adatabase/8.2.x

        $conn = Database::getConnection('default', 'frocole');

        $query = $conn
            ->select('courses', 'c')
            ->condition('c.CourseID', $id);
    
        //see https://www.drupal.org/docs/8/api/database-api/dynamic-queries/joins
        $query->join('users', 'u', 'c.LeraarUserID=u.UserID');
        $query->fields('c');
        $query->fields('u', ['Username']);
    
        $data = $query
            ->execute()
            ->fetchAssoc();
    
        //[Courses]
        $course_name = $data['CourseName'];
        $ipf = $data['IPF_RD_parameters'];
        $gpf = $data['GPF_RD_parameters'];
        $leraarID = $data['LeraarUserID'];
        $active = $data['CourseActive'];

        //[Leraar]
        $leraar = $data['Username'];
    
        //[Groups]
        $query = $conn
            ->select('groups', 'g')
            ->condition('g.CourseID', $id)
            ->fields('g');
        $data = $query
            ->execute()
            ->fetchAllAssoc('GroupID', \PDO::FETCH_ASSOC);
        
        //[Groups]
        $groups = "";
        
        foreach ($data as $record) {
            // Do something with each $record
            $groupID = $record['GroupID'];
            $group = $record['GroupNickname'];
            
            $export_url = Url::fromRoute('frocole.export_form', ['id' => $groupID], []);

            $groups .= '<tr><td>[<a href="'.$export_url->toString().'" title="'.t('Export feedback to CSV/Excel').'">'.str_pad($groupID, 4, '0', STR_PAD_LEFT).'</a>]</td><td>'.$group.'</td><td>'.$this->FetchGroupUsers($conn, $groupID).'</td></tr>';
            //$groups .= $this->FetchGroupUsers($conn, $groupID);
        }

        if (strlen($groups) === 0) {
            $groups = "<li><i>".t('No Groups')."</i>";
        }
        
        $groups = "<table><tr><th>GroupID</th><th>Group Nickname</th><th>Users</th></tr>".$groups."</table>";
        
        $url = Url::fromRoute('frocole.display_data');
        
        return [
        '#type' => 'markup',
        '#markup' => 
                    "<a href='".$url->toString()."'>".t('View All Courses')."</a>
                    <h1>$course_name</h1><br>
                    <strong>IPF_RD</strong>
                    <p>".$this->AxisToList($ipf)."</p>
                    <strong>GPF_RD</strong>
                    <p>".$this->AxisToList($gpf)."</p>
                    <strong>".t('Teacher')."</strong>
                    <p>[".str_pad($leraarID, 4, '0', STR_PAD_LEFT)."]&nbsp;$leraar</p>
                    <strong>".t('Active')."</strong>
                    <p>$active</p>
                    <strong>".t('Groups')."</strong>
                    <hr>
                    <p>$groups</p>"
        ];
    }

    /**
     * @return html list with all PFRD parameters.
     */
    private function AxisToList($pf)
    {
        return "<ul><li>".str_replace('/', '<li>', $pf)."</ul>";
    }
  
    /**
     * @return html list containing all users and their id's of a group.
     */
    private function FetchGroupUsers($conn, $groupID)
    {
        //[Groups]
         $query = $conn
              ->select('userandgrouprelations', 'r');

        $query->join('users', 'u', 'r.UserID=u.UserID');
        $query->condition('r.GroupID', $groupID);
        $query
            ->fields('r')
            ->fields('u', ['Username']);

        $data = $query
            ->execute()
            ->fetchAllAssoc('UserID', \PDO::FETCH_ASSOC);

         //[Users]
        $users = "";

        foreach ($data as $record) {
            // Do something with each $record
            $userID = $record['UserID'];
            $user = $record['Username'];

            $users .= "<tr>";
            //str_pad($userID, 4, '0', STR_PAD_LEFT)
            $users .= "<td>".$userID."</td><td>".$user."</td>";
            $users .= "</tr>";
        }

        if (strlen($users) === 0) {
            $users .= "<tr>";
            $users .= "<td span='2'>".t('No Users')."</td>";
            $users .= "<tr>";

            $users = "<table>".$users."</table>";
        } else {
            $users = "<table><tr><th>UserID</th><th>Username</th></tr>".$users."</table>";
        }


        return $users;
    }
}
