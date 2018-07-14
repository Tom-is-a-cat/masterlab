<?php
/**
 * Created by PhpStorm.
 */

namespace main\app\ctrl\project;

use main\app\async\email;
use main\app\classes\ProjectModuleFilterLogic;
use main\app\classes\UserAuth;
use main\app\ctrl\BaseUserCtrl;
use main\app\model\project\ProjectModel;
use main\app\model\project\ProjectVersionModel;
use main\app\model\project\ProjectModuleModel;
use main\app\classes\ProjectLogic;

/**
 * 项目模块
 */
class Module extends BaseUserCtrl
{

    public function __construct()
    {
        parent::__construct();
        parent::addGVar('top_menu_active', 'project');
    }

    public function index()
    {
        $data = [];
        $data['title'] = '浏览 版本';
        $data['nav_links_active'] = 'module';
        $this->render('gitlab/project/module.php', $data);
    }


    public function _new()
    {
        $data = [];
        $data['title'] = '项目分类';
        $this->render('gitlab/project/module_form.php', $data);
    }

    public function edit($id)
    {
        // @todo 判断权限:全局权限和项目角色
        $id = intval($id);
        if (empty($id)) {
            $this->error('Param Error', 'id_is_empty');
        }

        $uid = $this->getCurrentUid();
        $projectVersionModel = new ProjectModuleModel($uid);

        $version = $projectVersionModel->getRowById($id);
        if (!isset($version['name'])) {
            $this->error('Param Error', 'id_not_exist');
        }

        $data = [];
        $data['title'] = '项目分类';
        $data['version'] = $version;
        $this->render('gitlab/project/version_form.php', $data);
    }

    private function paramValid($projectVersionModel, $project_id, $name)
    {
        if (empty(trimStr($name))) {
            $this->ajaxFailed('param_error:name_is_null');
        }

        $version = $projectVersionModel->getByProjectIdName($project_id, $name);
        if (isset($version['name'])) {
            $this->ajaxFailed('param_error:name_exist');
        }
    }

    public function add($module_name, $description, $lead = 0, $default_assignee = 0)
    {
        if (isPost()) {
            $uid = $this->getCurrentUid();
            $project_id = intval($_REQUEST[ProjectLogic::PROJECT_GET_PARAM_ID]);
            $module_name = trim($module_name);
            $projectModuleModel = new ProjectModuleModel($uid);

            if($projectModuleModel->checkNameExist($project_id, $module_name)){
                $this->ajaxFailed('name is exist.', array(), 500);
            }

            $row = [];
            $row['project_id'] = $project_id;
            $row['name'] = $module_name;
            $row['description'] = $description;
            $row['lead'] = $lead;
            $row['default_assignee'] = $default_assignee;
            $row['ctime'] = time();

            $ret = $projectModuleModel->insert($row);
            if ($ret[0]) {
                $this->ajaxSuccess('add_success');
            } else {
                $this->ajaxFailed('add_failed', array(), 500);
            }
        }
        $this->ajaxFailed('add_failed', array(), 500);
    }


    public function update($id, $name, $description)
    {
        $id = intval($id);
        $uid = $this->getCurrentUid();
        $projectModuleModel = new ProjectModuleModel($uid);

        $module = $projectModuleModel->getRowById($id);
        if (!isset($module['name'])) {
            $this->ajaxFailed('param_error:id_not_exist');
        }

        $row = [];

        if (isset($name) && !empty($name)) {
            $row['name'] = $name;
        }
        if (isset($description) && !empty($description)) {
            $row['description'] = $description;
        }

        if (count($row) < 2) {
            $this->ajaxFailed('param_error:form_data_is_error '.count($row));
        }
        $ret = $projectModuleModel->updateById($id, $row);
        if ($ret[0]) {
            $this->ajaxSuccess('update_success');
        } else {
            $this->ajaxFailed('update_failed');
        }
    }

    public function fetchModule($module_id)
    {
        $projectModuleModel = new ProjectModuleModel();
        $final = $projectModuleModel->getById($module_id);
        if(empty($final)){
            $this->ajaxFailed('non data...');
        }else{
            $this->ajaxSuccess('success', $final);
        }
    }

    public function filterSearch($project_id, $name='')
    {
        $pageSize = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        if (isset($_GET['page'])) {
            $page = max(1, intval($_GET['page']));
        }

        $projectModuleFilterLogic = new ProjectModuleFilterLogic();
        list($ret, $list, $total) = $projectModuleFilterLogic->getModuleByFilter($project_id, $name, $page, $pageSize);

        if($ret){
            array_walk($list, function (&$value, $key){
                $value['ctime'] = format_unix_time($value['ctime'], time());
            });
        }

        $data['total'] = $total;
        $data['pages'] = ceil($total / $pageSize);
        $data['page_size'] = $pageSize;
        $data['page'] = $page;
        $data['modules'] = $list;
        $this->ajaxSuccess('success', $data);
    }

    public function delete($project_id, $module_id)
    {
        $projectModuleModel = new ProjectModuleModel();
        $projectModuleModel->removeById($project_id, $module_id);
        $this->ajaxSuccess('success');
    }
}
