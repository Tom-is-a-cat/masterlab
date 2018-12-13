<?php

/**
 * Created by PhpStorm.
 * User: sven
 * Date: 2017/7/7 0007
 * Time: 下午 3:56
 */

namespace main\app\classes;

use main\app\model\WidgetModel;
use main\app\classes\LogOperatingLogic;
use main\app\classes\PermissionGlobal;
use main\app\classes\PermissionLogic;
use main\app\classes\UserAuth;
use main\app\classes\UserLogic;
use main\app\classes\ProjectLogic;
use main\app\classes\IssueFilterLogic;
use main\app\model\user\UserModel;
use main\app\model\project\ProjectModel;
use main\app\model\agile\SprintModel;
use main\app\model\issue\IssueStatusModel;
use main\app\model\issue\IssueTypeModel;
use main\app\model\issue\IssuePriorityModel;
use main\app\model\project\ReportProjectIssueModel;
use main\app\model\project\ReportSprintIssueModel;


/**
 * 面板逻辑类
 * Class WidgetLogic
 * @package main\app\classes
 */
class WidgetLogic
{
    /**
     * 获取可用的面板列表
     * @return array
     */
    public function getAvailableWidget()
    {
        $model = new WidgetModel();
        $rows = $model->getAllItems();
        $widgetArr = [];
        foreach ($rows as $row) {
            if ($row['status'] == '1') {
                $row['pic'] = ROOT_URL.'gitlab/images/widget/'.$row['pic'];
                $row['parameter'] = json_decode($row['parameter']);
                $widgetArr[] = $row;
            }
        }
        return $widgetArr;
    }

    /**
     * @throws \Exception
     */
    public function getUserHaveJoinProjects($limit)
    {
        $userId = UserAuth::getId();
        if (isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id'])) {
            $userId = (int)$_REQUEST['user_id'];
        }
        if (PermissionGlobal::check($userId, PermissionGlobal::ADMINISTRATOR)) {
            $projectModel = new ProjectModel();
            $all = $projectModel->getAll(false);
            $i = 0;
            $projects = [];
            foreach ($all as &$item) {
                $i++;
                if ($i > $limit) {
                    break;
                }
                $projects[] = ProjectLogic::formatProject($item);
            }
        } else {
            $projects = PermissionLogic::getUserRelationProjects($userId, $limit);
        }
        return $projects;
    }

    /**
     * 格式化饼状图数据
     * @param $field
     * @param $rows
     * @return array
     * @throws \Exception
     */
    public static function formatChartJsPie($field, $rows)
    {
        $colorArr = [
            'red' => 'rgb(255, 99, 132)',
            'orange' => 'rgb(255, 159, 64)',
            'yellow' => 'rgb(255, 205, 86)',
            'green' => 'rgb(75, 192, 192)',
            'blue' => 'rgb(54, 162, 235)',
            'purple' => 'rgb(153, 102, 255)',
            'grey' => 'rgb(201, 203, 207)'
        ];
        $randColor = function () {
            return 'rgb(' . mt_rand(1, 50) . ', ' . mt_rand(50, 150) . ', ' . mt_rand(150, 255) . ')';
        };
        $pieConfig = [];
        $pieConfig['type'] = 'pie';
        $pieConfig['options']['responsive'] = true;
        $dataSetArr = [];
        $labels = [];
        switch ($field) {
            case 'assignee':
                $userModel = new UserModel();
                $userArr = $userModel->getAll();
                $label = '按经办人';
                $backgroundColor = [];
                $data = [];
                foreach ($rows as $item) {
                    $data[] = (int)$item['count'];
                    $color = $randColor();
                    if (!empty($colorArr)) {
                        $randKey = array_rand($colorArr);
                        if (isset($colorArr[$randKey])) {
                            $color = $colorArr[$randKey];
                            unset($colorArr[$randKey]);
                        }
                    }
                    $backgroundColor[] = $color;
                    $name = '';
                    if (isset($userArr[$item['id']])) {
                        $name = $userArr[$item['id']]['display_name'];
                    }
                    $labels[] = $name;
                }
                $dataSetArr['label'] = $label;
                $dataSetArr['backgroundColor'] = $backgroundColor;
                $dataSetArr['data'] = $data;
                break;
            case 'priority':
                $model = new IssuePriorityModel();
                $priorityArr = $model->getAllItem(true);
                $label = '按优先级';
                $backgroundColor = [];
                $data = [];
                foreach ($rows as $item) {
                    $data[] = (int)$item['count'];
                    $name = '';
                    if (isset($priorityArr[$item['id']])) {
                        $name = $priorityArr[$item['id']]['name'];
                    }
                    $color = $randColor();
                    if (isset($priorityArr[$item['id']])) {
                        $color = $priorityArr[$item['id']]['status_color'];
                    }
                    $backgroundColor[] = $color;

                    $labels[] = $name;
                }
                $dataSetArr['label'] = $label;
                $dataSetArr['backgroundColor'] = $backgroundColor;
                $dataSetArr['data'] = $data;
                break;
            case 'issue_type':
                $model = new IssueTypeModel();
                $typeArr = $model->getAll(true);
                $label = '按优先级';
                $backgroundColor = [];
                $data = [];
                foreach ($rows as $item) {
                    $data[] = (int)$item['count'];
                    $color = $randColor();
                    if (!empty($colorArr)) {
                        $randKey = array_rand($colorArr);
                        if (isset($colorArr[$randKey])) {
                            $color = $colorArr[$randKey];
                            unset($colorArr[$randKey]);
                        }
                    }
                    $backgroundColor[] = $color;
                    $name = '';
                    if (isset($typeArr[$item['id']])) {
                        $name = $typeArr[$item['id']]['name'];
                    }
                    $labels[] = $name;
                }
                $dataSetArr['label'] = $label;
                $dataSetArr['backgroundColor'] = $backgroundColor;
                $dataSetArr['data'] = $data;
                break;
            case 'status':
                $model = new IssueStatusModel();
                $statusArr = $model->getAll(true);
                $label = '按优先级';
                $backgroundColor = [];
                $data = [];
                foreach ($rows as $item) {
                    $data[] = (int)$item['count'];
                    $color = $randColor();
                    if (!empty($colorArr)) {
                        $randKey = array_rand($colorArr);
                        if (isset($colorArr[$randKey])) {
                            $color = $colorArr[$randKey];
                            unset($colorArr[$randKey]);
                        }
                    }
                    $backgroundColor[] = $color;
                    $name = '';
                    if (isset($statusArr[$item['id']])) {
                        $name = $statusArr[$item['id']]['name'];
                    }
                    $labels[] = $name;
                }
                $dataSetArr['data'] = $data;
                $dataSetArr['backgroundColor'] = $backgroundColor;
                $dataSetArr['label'] = $label;
                break;
            default:
                break;
        }

        $pieConfig['data']['datasets'][] = $dataSetArr;
        $pieConfig['data']['labels'] = $labels;
        return $pieConfig;
    }


    /**
     * 格式化柱状图格式
     * @param $rows
     * @return array
     */
    public static function formatChartJsBar($rows)
    {
        //print_r($rows);
        $colorArr = [
            'red' => 'rgb(255, 99, 132)',
            'orange' => 'rgb(255, 159, 64)',
            'yellow' => 'rgb(255, 205, 86)',
            'green' => 'rgb(75, 192, 192)',
            'blue' => 'rgb(54, 162, 235)',
            'purple' => 'rgb(153, 102, 255)',
            'grey' => 'rgb(201, 203, 207)'
        ];
        $barConfig = [];
        $barConfig['type'] = 'bar';

        $labels = [];

        $dataSetArr = [];
        $dataSetArr['label'] = '已解决';
        $dataSetArr['backgroundColor'] = $colorArr['green'];
        $data = [];
        foreach ($rows as $item) {
            $data[] = (int)$item['count_done'];
        }
        $dataSetArr['data'] = $data;
        $barConfig['data']['datasets'][] = $dataSetArr;

        $dataSetArr = [];
        $dataSetArr['label'] = '未解决';
        $dataSetArr['backgroundColor'] = $colorArr['red'];
        $data = [];
        foreach ($rows as $item) {
            $data[] = (int)$item['count_no_done'];
            $labels[] = $item['label'];
        }
        $dataSetArr['data'] = $data;
        $barConfig['data']['datasets'][] = $dataSetArr;

        $barConfig['data']['labels'] = $labels;
        return $barConfig;
    }
}