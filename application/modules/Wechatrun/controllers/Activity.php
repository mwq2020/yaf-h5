<?php
/****************************/
/****** 健步走活动入口 ********/
/****************************/

// use Yaf\Controller_Abstract;
class ActivityController extends Core\Base
{
    //初始化方法，每个控制器都会先执行这个
    // protected function init()
    // {
    //     \Yaf\Dispatcher::getInstance()->autoRender(false); 
    // }


    public function indexAction()
    {
        return false;
    }

    /**
     * 活动列表
     */
    public function listAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $activity_list = DB::table('w_step_activity','shop')->where(['company_id'=>$company_id])->get();
        $this->jsonSuccess($activity_list);
        // $this->jsonError($activity_list);
    
        // return false;
        // $this->getView()->display('User/index.phtml');
    }

    /**
     * 活动详情
     */
    public function infoAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            $this->jsonError('活动id不能为空');
        }
        $activity_info = DB::table('w_step_activity','shop')->where(['act_id'=>$act_id])->first();
        $this->jsonSuccess($activity_info); 
    }

    /**
     * 公司所有人排行
     */
    public function rankallAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            $this->jsonError('活动id不能为空');
        }

        $ret = DB::table('w_step_log','shop')  
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_user.company_id' => $company_id])
                ->orderBy('step_num_all','desc')
                ->get();

        $this->jsonSuccess($ret);
    }

    /**
     * 部门排行
     */
    public function rankdepartmentAction()
    {
        $company_id = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
        if(empty($company_id)){
            $this->jsonError('企业id不能为空');
        }
        $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 0;
        if(empty($department_id)){
            $this->jsonError('部门id不能为空');
        }
        $act_id = isset($_REQUEST['act_id']) ? intval($_REQUEST['act_id']) : 0;
        if(empty($act_id)){
            $this->jsonError('活动id不能为空');
        }

        $ret = DB::table('w_step_log','shop')  
                ->leftJoin('w_company_user','w_step_log.user_id','=','w_company_user.user_id')
                ->select(
                     DB::raw('SUM(w_step_log.step_num) AS step_num_all'),
                    'w_company_user.real_name',
                    'w_company_user.telphone',
                    'w_company_user.department_id',
                    'w_company_user.department_name',
                    'w_company_user.user_id'
                    )  
                ->groupBy('w_company_user.user_id')
                ->where(['w_company_user.company_id' => $company_id,'w_company_user.department_id' => $department_id])
                ->orderBy('step_num_all','desc')
                ->get();

        $this->jsonSuccess($ret);
    }


}