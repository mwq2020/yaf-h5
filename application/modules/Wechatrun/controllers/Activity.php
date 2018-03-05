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
        $activity_list = DB::table('w_step_activity')->where(['company_id'=>$company_id])->get();
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
        $activity_info = DB::table('w_step_activity')->where(['act_id'=>$act_id])->first();
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
            //$this->jsonError('活动id不能为空');
        }

        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        if(empty($user_id)){
            $this->jsonError('用户id不能为空');
        }

        $ret = DB::table('w_step_log')  
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
                ->where('w_step_log.data_time','>=',strtotime('2018-03-01'))
                ->where('w_step_log.data_time','<=',strtotime('2018-03-31'))
                ->orderBy('step_num_all','desc')
                ->get();

        $ranking_list = ['info'=>[],'list' => [],'one'=>[],'two'=>[],'three'=>[]];
        $ranking_num = 1;
        foreach($ret as $row){
            $row['ranking_num'] = $ranking_num;
            $ranking_list['list'][$ranking_num] = $row;
            $ranking_num++;
            if($row['user_id'] == $user_id) {
                $ranking_list['info'] = $row;
            }
        }

        if(empty($ranking_list['info'])){
            $user_info = DB::table('w_company_user')->where(['company_id' => $company_id,'user_id' => $user_id])->first();
            $ranking_list['info']['step_num_all']   = 0;
            $ranking_list['info']['real_name']      = $user_info['real_name'];
            $ranking_list['info']['telphone']       = $user_info['telphone'];
            $ranking_list['info']['department_id']  = $user_info['department_id'];
            $ranking_list['info']['department_name'] = $user_info['department_name'];
            $ranking_list['info']['user_id']        = $user_info['user_id'];
            $ranking_list['info']['ranking_num']    = count($ret)+1;
        }

        $list_count = count($ranking_list['list']);
        if($list_count >= 1){
            $ranking_list['one'] = $ranking_list['list'][1];
        }

        if($list_count >= 2){
            $ranking_list['two'] = $ranking_list['list'][2];
        }

        if($list_count >= 3){
            $ranking_list['three'] = $ranking_list['list'][3];
        }

        $this->jsonSuccess($ranking_list);
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
            //$this->jsonError('活动id不能为空');
        }
        $user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        if(empty($user_id)){
            $this->jsonError('用户id不能为空');
        }

        $ret = DB::table('w_step_log')  
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
                ->where('w_step_log.data_time','>=',strtotime('2018-03-01'))
                ->where('w_step_log.data_time','<=',strtotime('2018-03-31'))
                ->orderBy('step_num_all','desc')
                ->get();

        $ranking_list = ['info'=>[],'list' => [],'one'=>[],'two'=>[],'three'=>[]];
        $ranking_num = 1;
        foreach($ret as $row){
            $row['ranking_num'] = $ranking_num;
            $ranking_list['list'][$ranking_num] = $row;
            $ranking_num++;
            if($row['user_id'] == $user_id) {
                $ranking_list['info'] = $row;
            }
        }

        if(empty($ranking_list['info'])){
            $user_info = DB::table('w_company_user')->where(['company_id' => $company_id,'user_id' => $user_id])->first();
            $ranking_list['info']['step_num_all']   = 0;
            $ranking_list['info']['real_name']      = $user_info['real_name'];
            $ranking_list['info']['telphone']       = $user_info['telphone'];
            $ranking_list['info']['department_id']  = $user_info['department_id'];
            $ranking_list['info']['department_name'] = $user_info['department_name'];
            $ranking_list['info']['user_id']        = $user_info['user_id'];
            $ranking_list['info']['ranking_num']    = count($ret)+1;
        }


        $list_count = count($ranking_list['list']);
        if($list_count >= 1){
            $ranking_list['one'] = $ranking_list['list'][1];
        }

        if($list_count >= 2){
            $ranking_list['two'] = $ranking_list['list'][2];
        }

        if($list_count >= 3){
            $ranking_list['three'] = $ranking_list['list'][3];
        }

        $this->jsonSuccess($ranking_list);
    }


}