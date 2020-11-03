<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Addpage;
use Mail;
use Hash;
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
    */
    public function __construct()
    {
       $this->middleware('auth');
    }

//////////////////////////////////////////////////

    public function dashbaord($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;

        $user_dep = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
	        ->where('users.id','=',$id)
	        ->get()->first();
	   
	   $user_a = DB::table('individualusers')
        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
        ->where('individualusers.store_id',$user_dep->store_id)
        ->where('individualusers.department_id',$user_dep->department_id)
        ->where('addsalespositions.position_manager',0)
        ->get()->toArray();
        
        
        
        $ids1[] = $id;
        foreach($user_a as $aa){
            $ids1[] = $aa->user_id;
        }
	   
	    $is_manager = $user_dep->position_manager;

        $all_user = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
	        ->whereIn('individualusers.user_id', $ids1)
	        ->get()->toArray();
	        
	  
	    $is_boarding = DB::select('select * from `individualusers` where `user_id`='.$id.' AND `onboarding_authority` = 1');

        $applyforcourse = 0;
        if($user_dep->position_manager != 1){
            $apply_course = DB::table('trainingcontents')
            ->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')
            ->where('applycourses.user_id','=',$id)
            ->groupBy('applycourses.course_id')
            ->get()->toArray();
            $applyforcourse= count($apply_course);
        }else{
            foreach ($all_user as $value) {
                $ids = $value->user_id;
                $apply_course = DB::table('trainingcontents')
                ->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')
                ->where('applycourses.user_id','=',$ids)
                ->groupBy('applycourses.course_id')
                ->get()->toArray();
                $c= count($apply_course);
                if($c != 0){
                    $applyforcourse = $applyforcourse + $c; 
                }
            }
        }
        
        $completecourse = 0;
        if($user_dep->position_manager != 1){
           

            $complete_course = DB::select('select * from `trainingcontents` inner join `applycourses` on `applycourses`.`course_id` = `trainingcontents`.`trainingcontent_id` inner join `assessmentanswerbyusers` on `assessmentanswerbyusers`.`user_id` = `applycourses`.`user_id` inner join `assessments` on `assessments`.`course_id` = `applycourses`.`course_id` where `applycourses`.`user_id` = '.$id.' and `applycourses`.`course_id` = `assessmentanswerbyusers`.`c_id` group by `applycourses`.`course_id`');

            $completecourse= count($complete_course);
        }else{
            

            $complete_course = DB::select('select * from `trainingcontents` inner join `applycourses` on `applycourses`.`course_id` = `trainingcontents`.`trainingcontent_id` inner join `stores` on `stores`.`store_id` = `trainingcontents`.`store_id` inner join `organizations` on `organizations`.`id` = `stores`.`org_id` inner join `assessments` on `assessments`.`course_id` = `applycourses`.`course_id` inner join `users` on `users`.`id` = `applycourses`.`tranier_id` inner join `assessmentanswerbyusers` on `assessmentanswerbyusers`.`user_id` = `applycourses`.`user_id` where `applycourses`.`course_id` = `assessmentanswerbyusers`.`c_id` and `applycourses`.`user_id` in ('.implode(",",$ids1).') group by `applycourses`.`course_id`, `applycourses`.`user_id`');
            
            $completecourse = count($complete_course);
        }

        
        $Tasksassigned =0;
        if($user_dep->position_manager != 1){
            $Task_sassigned = DB::select('select * from `individualusers` inner join `boardingcontents` on  `individualusers`.`store_id`=`boardingcontents`.`store_id` where `individualusers`.`user_id`='.$id.' AND `individualusers`.`onboarding_authority` = 1 AND `boardingcontents`.`department_id` = individualusers.department_id AND `boardingcontents`.`position_id` = '.$user_dep->position_id);

           
            $Tasksassigned = count($Task_sassigned);
            $manager = 0;
        }else{
            foreach ($all_user as $value) {
                $ids = $value->user_id;
                
                $Task_sassigned = DB::select('select * from `individualusers` inner join `boardingcontents` on  `individualusers`.`store_id`=`boardingcontents`.`store_id` where `individualusers`.`user_id`='.$ids.'  AND `individualusers`.`onboarding_authority` = 1 AND `boardingcontents`.`department_id` = individualusers.department_id AND `boardingcontents`.`position_id` = '.$value->position_id);

                $ta= count($Task_sassigned);
                if($ta != 0){
                    $Tasksassigned = $Tasksassigned + $ta; 
                }
            }
            $manager = 1;
        }

        $bordicomplete = 0;
        if($user_dep->position_manager != 1){
            $bordi_complete = DB::table('onboardingbyusers')->join('boardingcontents', 'boardingcontents.boardingcontent_id', '=', 'onboardingbyusers.onboarding_id')->where('user_id','=', $id)->groupBy('onboardingbyusers.onboarding_id')->get()->toArray();
            $bordicomplete = count($bordi_complete);
        }else{
            foreach ($all_user as $value) {
                $ids = $value->user_id;

                $bordi_complete = DB::table('onboardingbyusers')->join('boardingcontents', 'boardingcontents.boardingcontent_id', '=', 'onboardingbyusers.onboarding_id')->where('user_id','=', $ids)->groupBy('onboardingbyusers.onboarding_id')->get()->toArray();

                
                $bc= count($bordi_complete);

                if($bc != 0){
                    $bordicomplete = $bordicomplete + $bc; 
                }
            }
            
        }

        $ass_performance = 0;
        $totalass_ques_n = 0;
        $i=0;
        if($user_dep->position_manager != 1){
            $all_user = DB::table('individualusers')
                // ->join('individualusers', 'individualusers.user_id', '=', 'users.id')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
                ->join('applycourses','applycourses.user_id','=','users.id')
                ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'applycourses.course_id')
                ->where('individualusers.user_id',$id)
                ->get()->toArray();

            if(isset($all_user)){
                $c=0;
                $sum = 0;
                $score = 0;
                $total_question = 0;
                foreach($all_user as $all_store_v){
                    if($all_store_v->user_id != $all_store_v->tranier_id){
                        
                        $c++;
                        /// 
                            $tq = DB::table('assessments')->where('assessments.course_id','=',$all_store_v->course_id)->get()->toArray();
                            
                            // echo "<pre>";
                            // print_r($tq);
                            // echo "<pre>";
                            
                            // $total_question = count($tq) + $total_question;
                            $total_question = count($tq);
                            
                            $ques_Ans = DB::table('individualusers')
                                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                                ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
                                ->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')
                                ->join('assessments', 'assessments.course_id', '=', 'trainingcontents.trainingcontent_id')
                                ->join('assessmentquestionansers', 'assessmentquestionansers.assessmentquestion_id', '=', 'assessments.assessment_id')
                                ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.assessmentquestionanser_id', '=', 'assessmentquestionansers.assessmentquestionanser_id')
                                ->where('assessments.course_id','=',$all_store_v->course_id)
                                ->where('assessmentanswerbyusers.user_id','=',$all_store_v->user_id)
                                ->groupBy('assessments.assessment_id','assessmentquestionansers.answer')
                                ->get()->toArray();

                            if(isset($ques_Ans) && !empty($ques_Ans)){
                                $allcourse = array();
                                foreach($ques_Ans as $current) {
                                    $dsid = $current->course_id;
                                    $allcourse[] = $current;
                                }
                                
                                $correct_answer = array();
                                foreach($allcourse as $correct) {
                                    if($correct->answer == 'true'){
                                      $correct_answer[] = $correct->ans_id;
                                    }
                                }
                                
                                if(!empty($correct_answer)){
                                    $c_ans = count($correct_answer);
                                }else{
                                    $c_ans = 0;
                                }
                                $marks = 100;
                                $pre = ($marks/$total_question);
                                $score =  round($pre*$c_ans, 2);
                                
                                $sum = $sum+$score;
                                //  echo count($tq)." ";
                            }
                        ///
                    }
                }
                if($c == 0){
                    $c=1;
                }
               
                $ass_performance =  round($sum/$c, 2);
            }
            
            
        }else{ 
            $user_a = DB::table('individualusers')
            ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
            ->where('individualusers.store_id',$user_dep->store_id)
            ->where('individualusers.department_id',$user_dep->department_id)
            ->where('addsalespositions.position_manager',0)
            ->get()->toArray();
            
            
            
            $idd[] = $id;
            foreach($user_a as $aa){
                $idd[] = $aa->user_id;
            }
            
        
            $all_user = DB::table('individualusers')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
                ->join('applycourses','applycourses.user_id','=','users.id')
               
                ->whereIn('users.id',$idd)
                
                ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'applycourses.course_id')
                ->groupBy('applycourses.user_id','applycourses.course_id')
                
                ->get()->toArray();
            
            
            
            if(isset($all_user)){
                $c=0;
                $sum = 0;
                $score = 0;
                $total_question = 0;
                foreach($all_user as $all_store_v){
                    if($all_store_v->user_id != $all_store_v->tranier_id){
                        // echo "a "."<br>";
                        $c++;
                        /// 
                            $tq = DB::table('assessments')->where('assessments.course_id','=',$all_store_v->course_id)->get()->toArray();
                            // $total_question = count($tq) + $total_question;
                            $total_question = count($tq);
                            $ques_Ans = DB::table('individualusers')
                                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                                ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
                                ->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')
                                ->join('assessments', 'assessments.course_id', '=', 'trainingcontents.trainingcontent_id')
                                ->join('assessmentquestionansers', 'assessmentquestionansers.assessmentquestion_id', '=', 'assessments.assessment_id')
                                ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.assessmentquestionanser_id', '=', 'assessmentquestionansers.assessmentquestionanser_id')
                                ->where('assessments.course_id','=',$all_store_v->course_id)
                                ->where('assessmentanswerbyusers.user_id','=',$all_store_v->user_id)
                                ->groupBy('assessments.assessment_id','assessmentquestionansers.answer')
                                ->get()->toArray();

                            if(isset($ques_Ans) && !empty($ques_Ans)){
                                $allcourse = array();
                                foreach($ques_Ans as $current) {
                                    $dsid = $current->course_id;
                                    $allcourse[] = $current;
                                }
                                
                                $correct_answer = array();
                                foreach($allcourse as $correct) {
                                    if($correct->answer == 'true'){
                                      $correct_answer[] = $correct->ans_id;
                                    }
                                }
                                
                                if(!empty($correct_answer)){
                                    $c_ans = count($correct_answer);
                                }else{
                                    $c_ans = 0;
                                }
                                $marks = 100;
                                $pre = ($marks/$total_question);
                                $score =  round($pre*$c_ans, 2);
                                
                                $sum = $sum+$score;
                                // echo $score."<br>";
                            }
                        ///
                    }
                }
                if($c == 0){
                    $c=1;
                }
                $ass_performance =  round($sum/$c, 2);
            }
            
        }
        
        return view('user.dashboard',compact('manager','is_boarding','ass_performance','applyforcourse','completecourse','Tasksassigned','bordicomplete'));

    }

//////////////////////////////////////////////////

    public function traininglibrary($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        
        $id = Auth::user()->id;

        $user_dep = DB::table('individualusers')
        ->join('users', 'users.id', '=', 'individualusers.user_id')
        ->where('users.id','=',$id)
        // ->select('individualusers.department_id')
        ->get()->first();

        
        $storeagent = DB::select('select * from `addsalespositions` inner join `individualusers` on `individualusers`.`position_id` = `addsalespositions`.`addsalesposition_id` where `individualusers`.`user_id` = '.$id.'');
        $is_manager = $storeagent[0]->position_manager;

        // echo $is_manager;
        if($is_manager == 1){
            $SalesSoftSkills = DB::select('select * from `individualusers` inner join `stores` on `stores`.`store_id` = `individualusers`.`store_id` inner join `organizations` on `organizations`.`id` = `stores`.`org_id` inner join `trainingcontents` on `trainingcontents`.`organization_id` = `organizations`.`id` where `trainingcontents`.`department_id` = individualusers.department_id AND `individualusers`.`user_id` = '.$id.' ORDER BY trainingcontents.course_name ASC');
        }else{
            $SalesSoftSkills = DB::select('select * from `individualusers` inner join `stores` on `stores`.`store_id` = `individualusers`.`store_id` inner join `organizations` on `organizations`.`id` = `stores`.`org_id` inner join `trainingcontents` on `trainingcontents`.`organization_id` = `organizations`.`id` where `trainingcontents`.`department_id` = individualusers.department_id AND `trainingcontents`.`sales_position` = individualusers.`position_id` AND `individualusers`.`user_id` = '.$id.' ORDER BY trainingcontents.course_name ASC');
        }
        
        $inter_skill = DB::table('trainingcontents')
        ->join('topics', 'topics.topic_id', '=', 'trainingcontents.topic')
        ->where('topics.topic_id',1)
        // ->where('trainingcontents.department_id',$user_dep->department_id)
        ->orderby('trainingcontents.course_name','ASC')
        ->get();
        
        
        $departments = DB::table('departments')->where('department_id',$user_dep->department_id)->get();

        $topic = DB::table('topics')->get();
      
        return view('user.training_library',compact('inter_skill','departments','topic','SalesSoftSkills')); 
    }
    
    //////////////////////////////////////////////////

    public function appVideo($value='')
    {
        $id = Auth::user()->id;
        
        $u_det = DB::table('individualusers')->join('users', 'individualusers.user_id', '=', 'users.id')->where('users.id','=',$id)->get();
        
        $app_video = DB::table('all_settings')
        ->where('type','app_video')
        ->where('store',$u_det[0]->store_id)
        ->get()->toArray();
        
        return view('user.user_app_video',compact('u_det','app_video')); 
    }
    
    ////////////////////////////////////////////////////////////////////////////
    public function saveAppVideo(Request $request)
    {
        $role = Auth::user()->role_id;
        $sessionid = Auth::user()->id;
        
        $app_video_upload = request('app_video_upload');

        if(isset($app_video_upload) && !empty($app_video_upload)){
            foreach ($app_video_upload as $vkey => $video_uploadvalue) {
                 $video_uploadvaluename = time().'.'.$video_uploadvalue->getClientOriginalExtension();
                 $video_uploadvalue->move(public_path('video'), $video_uploadvaluename);
                 $video_uploadvaluenames = $video_uploadvaluename; 
            }
        }else{
            $video_uploadvaluenames = request('empty_app_video_upload');
        }

        $v_id = request('v_id');
        $v_s_id = request('v_s_id');
        if(!empty($app_video_upload)){
            if(!empty($v_id)){
                $data=array('name'=>$video_uploadvaluenames);
                $updatedata = DB::table('all_settings')->where('v_id', $v_id)->limit(1)->update($data);
                if($updatedata != ""){
                    return back()->with('success','Video updated successfully!');
                }else{
                    return back()->with('error','Something Wrong!'); 
                }
            }else{
                $savedata1 = array("type"=>"app_video","name"=>$video_uploadvaluenames,"store"=>$v_s_id);
                $inserdata = DB::table('all_settings')->insertGetId($savedata1);
                if($inserdata != ""){
                    return back()->with('success','Video uploaded successfully!');
                }else{
                    return back()->with('error','Something Wrong!'); 
                }
            }
             
        }
    }

//////////////////////////////////////////////////

    public function topicdetail($id='')
    {
        # code...
        $role = Auth::user()->role_id;
        $sessionid = Auth::user()->id;

        $storeagent = DB::select('select * from `addsalespositions` inner join `individualusers` on `individualusers`.`position_id` = `addsalespositions`.`addsalesposition_id` where `individualusers`.`user_id` = '.$sessionid.'');
        $is_manager = $storeagent[0]->position_manager;

        $course_details = DB::table('trainingcontents')->where('trainingcontent_id','=',$id)->get();
        // echo "<pre>";
        // print_r($course_details);
        // echo "</pre>";
        $course_details_doc = DB::table('trainingcontentdocs')->where('trainingcontent_id','=',$id)->get();
        $course_doc_app = DB::table('trainingcontentdocs')->where('trainingcontent_id','=',$id)->get()->toArray();
        $textdata = DB::table('meetingboxs')->get()->toArray();

       return view('user.course_details',compact('is_manager','course_details','course_details_doc','course_details_doc_app','course_doc_app','textdata'));
    }
//////////////////////////////////////////////////

    public function applyCourse(Request $request)
    {
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        if (! auth()->check() && $role !='1') {
            return redirect()->to( '/login' );
        }
        $course_id = trim(request('course_id'));
        $data=array('course_id'=>$course_id,"user_id"=>$id);
        $inserdata = DB::table('applycourses')->insert($data);
        if($inserdata == 1){
            return redirect('user/topic-detail/'.$course_id.'')->with('success','Apply successfully!');
            //return back()->with('success','Organization created successfully!');
        }else{
           return redirect('user/topic-detail/'.$course_id.'')->with('error','Something Wrong!');
        } 
    }

////////////////////////////////////////////////////////////////////////////


    public function trainingAssessmentResponse($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        
        $user_dep = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
	        ->where('users.id','=',$id)
	        ->get()->first();
	   
	   $user_a = DB::table('individualusers')
        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
        ->where('individualusers.store_id',$user_dep->store_id)
        ->where('individualusers.department_id',$user_dep->department_id)
        ->where('addsalespositions.position_manager',0)
        ->get()->toArray();
        
        
        $idd[] = $id;
        foreach($user_a as $aa){
            $idd[] = $aa->user_id;
        }

        $all_user = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
	       
	        ->whereIn('individualusers.user_id', $idd)
	        ->get()->toArray();
	        
	   // $ids[] = $id;
        foreach ($all_user as $value) {
            $ids[] = $value->user_id;
        }
        
        if($user_dep->position_manager != 1){
            $users = DB::table('assessments')
            ->join('stores', 'stores.store_id', '=', 'assessments.store_id')
            ->join('applycourses', 'applycourses.course_id', '=', 'assessments.course_id')
            ->join('organizations', 'organizations.id', '=', 'assessments.org_id')
            ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'assessments.course_id')
            ->join('users', 'users.id', '=', 'applycourses.user_id')
            ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.c_id', '=', 'applycourses.course_id')
            ->where('users.id','=',$id)
            ->orderBy('applycourses.applycourse_id','DESC')
            ->groupBy('applycourses.user_id','applycourses.course_id')
            
            ->get()->toArray();
        }else{
            $users = DB::table('assessments')
            ->join('stores', 'stores.store_id', '=', 'assessments.store_id')
            ->join('applycourses', 'applycourses.course_id', '=', 'assessments.course_id')
            ->join('organizations', 'organizations.id', '=', 'assessments.org_id')
            ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'assessments.course_id')
            ->join('users', 'users.id', '=', 'applycourses.user_id')
            ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.c_id', '=', 'applycourses.course_id')
            
            ->whereIn('users.id', $idd)
            
            
            ->orderBy('applycourses.applycourse_id','DESC')
            ->groupBy('applycourses.user_id','applycourses.course_id')
            ->get()->toArray();
        }
        

        $totaldata = DB::table('assessments')->join('stores', 'stores.store_id', '=', 'assessments.store_id')->join('applycourses', 'applycourses.course_id', '=', 'assessments.course_id')->join('organizations', 'organizations.id', '=', 'assessments.org_id')->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'assessments.course_id')->join('users', 'users.id', '=', 'applycourses.user_id')->where('applycourses.user_id','=',$id)->groupBy('assessments.assessment_id')->get()->toArray();


        if(isset($totaldata) && !empty($totaldata)){
            $assessmentsid = array();
            foreach ($totaldata as $totaldatakey => $totaldatavalue) {
                # code...
                $assessmentsid[] =  $totaldatavalue->assessment_id;
            }
            $totalcourse = array();
            foreach($totaldata as $current) {
                $dsid = $current->course_id;

                $totalcourse[$dsid][] = $current; // use $dsid as common array key for now
            }
            $questionAnswersstrue = DB::table('assessmentquestionansers')->join('assessmentanswerbyusers', 'assessmentanswerbyusers.assessmentquestionanser_id', '=', 'assessmentquestionansers.assessmentquestionanser_id')->join('assessments', 'assessments.assessment_id', '=', 'assessmentquestionansers.assessmentquestion_id')->whereIn('assessmentquestionansers.assessmentquestion_id', array($assessmentsid))->where('assessmentquestionansers.answer','true')->groupBy('assessmentquestionansers.assessmentquestion_id')->get()->toArray();
            $totalanswer  = array();
            foreach($questionAnswersstrue as $current1) {
                $dsid = $current1->course_id;
                $totalanswer[$dsid][] = $current1; // use $dsid as common array key for now
            } 
        }
        return view('user.user_assessment_response',compact('users'));
        // return view('user.user_assessment_response',compact('users','totalcourse','totalanswer'));
    }

////////////////////////////////////////////////////////////////////////////


    public function AssessmentResponse($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        
        $user_dep = DB::table('individualusers')
            ->join('users', 'users.id', '=', 'individualusers.user_id')
            ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
            ->where('users.id','=',$id)
            ->get()->first();
       
       $user_a = DB::table('individualusers')
        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
        ->where('individualusers.store_id',$user_dep->store_id)
        ->where('individualusers.department_id',$user_dep->department_id)
        ->where('addsalespositions.position_manager',0)
        ->get()->toArray();
        
        $idd[] = $id;
        foreach($user_a as $aa){
            $idd[] = $aa->user_id;
        }

        $all_user = DB::table('individualusers')
            ->join('users', 'users.id', '=', 'individualusers.user_id')
            ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
            ->whereIn('individualusers.user_id', $idd)
            ->get()->toArray();

        foreach ($all_user as $value) {
            $ids[] = $value->user_id;
        }
        
        if($user_dep->position_manager != 1){
            $users = DB::table('assessments')
            ->join('stores', 'stores.store_id', '=', 'assessments.store_id')
            ->join('applycourses', 'applycourses.course_id', '=', 'assessments.course_id')
            ->join('organizations', 'organizations.id', '=', 'assessments.org_id')
            ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'assessments.course_id')
            ->join('users', 'users.id', '=', 'applycourses.user_id')
            ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.c_id', '=', 'applycourses.course_id')
            ->where('users.id','=',$id)
            // ->where('assessments.status','=',1)
            // ->groupBy('users.id','trainingcontents.trainingcontent_id')
            ->groupBy('applycourses.user_id','applycourses.course_id','applycourses.tranier_id')
            // ->groupBy('trainingcontents.trainingcontent_id')
            ->get()->toArray();
        }else{
            $users = DB::table('assessments')
            ->join('stores', 'stores.store_id', '=', 'assessments.store_id')
            ->join('applycourses', 'applycourses.course_id', '=', 'assessments.course_id')
            ->join('organizations', 'organizations.id', '=', 'assessments.org_id')
            ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'assessments.course_id')
            ->join('users', 'users.id', '=', 'applycourses.user_id')
            ->join('assessmentanswerbyusers', 'assessmentanswerbyusers.c_id', '=', 'applycourses.course_id')
            // ->where('users.id','=',$id)
            ->whereIn('users.id', $idd)
            // ->where('assessments.status','=',1)
            // ->groupBy('users.id','trainingcontents.trainingcontent_id')
            ->groupBy('applycourses.user_id','applycourses.course_id','applycourses.tranier_id')
            // ->groupBy('trainingcontents.trainingcontent_id')
            ->get()->toArray();
        }
        // echo "<pre>";
        // print_r($users);
        // echo "</pre>";
        return view('user.assessment_response',compact('users'));
        // return view('user.user_assessment_response',compact('users','totalcourse','totalanswer'));
    }
////////////////////////////////////////////////////////////////////////////

    public function feedbackResponses($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;

        $user_dep = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
	        ->where('users.id','=',$id)
	        ->get()->first();

        $all_user = DB::table('individualusers')
	        ->join('users', 'users.id', '=', 'individualusers.user_id')
	        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
	        ->where('individualusers.store_id',$user_dep->store_id)
	        ->where('individualusers.department_id',$user_dep->department_id)
	        ->where('addsalespositions.position_manager',0)
	        // ->whereNotIn('individualusers.user_id', [$id])
	        ->get()->toArray();
	        
	    $ids[] = $id;
        foreach ($all_user as $value) {
            $ids[] = $value->user_id;
        }
        
        
        if($user_dep->position_manager != 1){
            $users = DB::table('individualusers')
            ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
            ->join('organizations', 'organizations.id', '=', 'stores.org_id')
            ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
            ->join('feedbackquestions', 'feedbackquestions.course_id', '=', 'trainingcontents.trainingcontent_id')
            ->join('feedbackanswerbyusers', 'feedbackanswerbyusers.user_id', '=', 'individualusers.user_id')
            
            ->join('users', 'users.id', '=', 'feedbackanswerbyusers.user_id')
            ->groupBy('users.id')
            ->where('individualusers.user_id','=',$id)
            ->get()->toArray();
        }else{
            $users = DB::table('individualusers')
            ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
            ->join('organizations', 'organizations.id', '=', 'stores.org_id')
            ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
            ->join('feedbackquestions', 'feedbackquestions.course_id', '=', 'trainingcontents.trainingcontent_id')
            ->join('feedbackanswerbyusers', 'feedbackanswerbyusers.user_id', '=', 'individualusers.user_id')
            
            ->join('users', 'users.id', '=', 'feedbackanswerbyusers.user_id')
            ->groupBy('users.id')
            ->whereIn('individualusers.user_id', $ids)
            ->get()->toArray();
        }

        return view('user.user_feedback_responses',compact('users'));
    }

////////////////////////////////////////////////////////////////////////////

    public function feedbackviewAnswer($value='',$user_id='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        
        $questionAnswer = DB::table('individualusers')
            ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
            ->join('organizations', 'organizations.id', '=', 'stores.org_id')
            ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
            ->join('feedbackquestions', 'feedbackquestions.course_id', '=', 'trainingcontents.trainingcontent_id')
            ->join('feedbackquestionansers', 'feedbackquestionansers.feedbackquestion_id', '=', 'feedbackquestions.feedbackquestion_id')
            ->join('feedbackanswerbyusers', 'feedbackanswerbyusers.feedbackquestionanser_id', '=', 'feedbackquestionansers.feedbackquestionanser_id')
            ->where('feedbackquestions.course_id','=',$value)
            ->where('feedbackanswerbyusers.user_id','=',$user_id)
            ->groupBy('feedbackanswerbyusers.user_id','feedbackanswerbyusers.f_course_id')
            ->get()->toArray();
        
        return view('user.view_feed_answer_details',compact('questionAnswer'));
    }
    
////////////////////////////////////////////////////////////////////////////

    public function DeleteFeedbackByUser($course_id='',$user_id='')
    {
        
        $Answer_id = DB::table('feedbackanswerbyusers')
        ->join('feedbackquestions', 'feedbackquestions.feedbackquestion_id', '=', 'feedbackanswerbyusers.feedback_question_id')
        ->where('feedbackanswerbyusers.user_id','=',$user_id)
        ->where('feedbackquestions.course_id','=',$course_id)
        ->get()->toArray();

        $ids_to_delete=array();
        
        if(!empty($Answer_id)){
            foreach ($Answer_id as $key => $value) {
                $ids_to_delete[] = $value->ans_id;
            }
        }
 
        DB::table('feedbackanswerbyusers')->whereIn('ans_id', $ids_to_delete)->delete(); 
        return redirect('user/feedback-responses')->with('success','Feedback Deleted successfully!');  
    }

////////////////////////////////////////////////////////////////////////////

    public function reports($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;

        $user_dep = DB::table('individualusers')
        ->join('users', 'users.id', '=', 'individualusers.user_id')
        ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
        ->where('users.id','=',$id)
        ->get()->first();

        
        
        $user_a = DB::table('individualusers')
        ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
        ->where('individualusers.store_id',$user_dep->store_id)
        ->where('individualusers.department_id',$user_dep->department_id)
        ->where('addsalespositions.position_manager',0)
        ->get()->toArray();
        
        
        
        $idd[] = $id;
        foreach($user_a as $aa){
            $idd[] = $aa->user_id;
        }
        
        if($user_dep->position_manager == 1){
            $all_user = DB::table('individualusers')
                // ->join('individualusers', 'individualusers.user_id', '=', 'users.id')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                // ->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
                ->join('applycourses','applycourses.user_id','=','users.id')
                ->whereIn('individualusers.user_id', $idd)
                
                ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'applycourses.course_id')
                ->groupBy('applycourses.user_id','applycourses.course_id','applycourses.tranier_id')
                ->get()->toArray();
                

            $bord_user = DB::table('individualusers')
                // ->join('individualusers', 'individualusers.user_id', '=', 'users.id')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')

                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')

                
                ->whereIn('individualusers.user_id', $idd)
                ->where('individualusers.onboarding_authority',1)
                ->select('individualusers.user_id','users.name','departments.department_id','departments.department_name','stores.store_id','stores.store_name','organizations.org_name','individualusers.position_id', 'addsalespositions.position_name')
                // ->whereNotIn('individualusers.user_id', [$id])
                ->get()->toArray();

            $manager = 1;
        }else{
            $all_user = DB::table('individualusers')
                // ->join('individualusers', 'individualusers.user_id', '=', 'users.id')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
                ->join('applycourses','applycourses.user_id','=','users.id')
                ->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'applycourses.course_id')
                ->where('individualusers.user_id',$id)
                ->groupBy('applycourses.user_id','applycourses.course_id','applycourses.tranier_id')
                ->get()->toArray();

            $bord_user = DB::table('individualusers')
                ->join('stores', 'stores.store_id', '=', 'individualusers.store_id')
                ->join('organizations', 'organizations.id', '=', 'stores.org_id')
                ->join('users', 'users.id', '=', 'individualusers.user_id')
                ->join('departments', 'departments.department_id', '=', 'individualusers.department_id')
                ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
                ->where('individualusers.user_id',$id)
                ->where('individualusers.onboarding_authority',1)
                ->select('individualusers.user_id','users.name','departments.department_id','departments.department_name','stores.store_id','stores.store_name','organizations.org_name','individualusers.position_id', 'addsalespositions.position_name')
                ->get()->toArray();
            
            $manager = 0;
        }


        $departments = DB::table('departments')->get()->toArray();


        $all_store= DB::table('stores')
            ->join('organizations', 'organizations.id', '=', 'stores.org_id')
            ->join('individualusers', 'individualusers.store_id', '=', 'stores.store_id')
            ->where('individualusers.user_id',$id)
            ->get()->toArray();

        $trainer = DB::table('applycourses')->join('trainingcontents', 'trainingcontents.trainingcontent_id', '=', 'applycourses.course_id')->join('trainers', 'trainers.trainer_store_id', '=', 'trainingcontents.store_id')->where('applycourses.user_id','=',$id)->groupBy('trainers.trainer_id')->get()->toArray();

        
        $onboardingcontentsData = DB::table('individualusers')->join('stores', 'stores.store_id', '=', 'individualusers.store_id')->join('organizations', 'organizations.id', '=', 'stores.org_id')->join('departments', 'departments.department_id', '=', 'individualusers.department_id')->where('individualusers.user_id','=',$id)->groupBy('individualusers.user_id')->get()->toArray();


        $totalboarding  = array();
        
        $totalboardingbyUsers  = array();
        $bordi = DB::table('onboardingbyusers')->where('user_id','=', $id)->get()->toArray();
        foreach ($bordi as $key => $bordivalue) {
        	# code...
        	$dsid = $bordivalue->onboarding_id;
        	$totalboardingbyUsers[$dsid][] = $bordivalue; // use $dsid as common array key for now
        }

        $Tasksassigned = DB::select('select * from `individualusers` inner join `stores` on `stores`.`store_id` = `individualusers`.`store_id` inner join `organizations` on `organizations`.`id` = `stores`.`org_id` inner join `boardingcontents` on `boardingcontents`.`store_id` = `stores`.`store_id` where `boardingcontents`.`department_id` = individualusers.department_id AND `individualusers`.`position_id` = boardingcontents.`position_id` and `individualusers`.`user_id` = '.$id.' AND `individualusers`.`onboarding_authority` = 1');

        $bordicomplete = DB::table('onboardingbyusers')->join('boardingcontents', 'boardingcontents.boardingcontent_id', '=', 'onboardingbyusers.onboarding_id')->where('user_id','=', $id)->groupBy('onboardingbyusers.onboarding_id')->get()->toArray();
        
        
        return view('user.user_reports',compact('bord_user','manager','all_user','all_store','departments','trainer','users','totalcourse','totalanswer','totalboarding','totalboardingbyUsers','Tasksassigned','onboardingcontentsData','Tasksassigned','bordicomplete'));
    }



    public function AssmentFeedBackQuestionAnswer($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        $questionAnswer = DB::table('individualusers')->join('stores', 'stores.store_id', '=', 'individualusers.store_id')->join('organizations', 'organizations.id', '=', 'stores.org_id')->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')->join('assessments', 'assessments.course_id', '=', 'trainingcontents.trainingcontent_id')->where('individualusers.user_id','=',$id)->where('assessments.status','=',0)->get()->toArray();
             if(isset($questionAnswer) && !empty($questionAnswer)){
                foreach ($questionAnswer as $key => $value) {
                    $ids[] = $value->assessment_id;
                }
                $answer = DB::table('assessmentquestionansers')->whereIn('assessmentquestion_id', $ids)->get();
        }
        return view('user.assessment-responses-answer',compact('questionAnswer','answer'));
    }



    public function FeedBackQuestionAnswer($value='')
    {
        # code...
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        $questionAnswer = DB::table('individualusers')->join('stores', 'stores.store_id', '=', 'individualusers.store_id')->join('organizations', 'organizations.id', '=', 'stores.org_id')->join('trainingcontents', 'trainingcontents.store_id', '=', 'stores.store_id')->join('applycourses', 'applycourses.course_id', '=', 'trainingcontents.trainingcontent_id')->join('feedbackquestions', 'feedbackquestions.course_id', '=', 'trainingcontents.trainingcontent_id')->where('individualusers.user_id','=',$id)->where('feedbackquestions.status','=',0)->groupBy('feedbackquestions.feedbackquestion_id')->get()->toArray();
             if(isset($questionAnswer) && !empty($questionAnswer)){
                foreach ($questionAnswer as $key => $value) {
                    $ids[] = $value->feedbackquestion_id;
                }
                $answer = DB::table('feedbackquestionansers')->whereIn('feedbackquestion_id', $ids)->get();
        }
        return view('user.feedback_question_answer',compact('questionAnswer','answer'));
    }


////////////////////////////////////////////////////////////////////////////

    function OnBoardingSchedule(){

        $taskdays = DB::table('taskdays')->get();
        $responsiblepartys = DB::table('responsiblepartys')->get();
        $role = Auth::user()->role_id;
        $id = Auth::user()->id;
        $ids[] = $id;
        $names[]= Auth::user()->name;

        $user_dep = DB::table('individualusers')
            ->join('users', 'users.id', '=', 'individualusers.user_id')
            ->join('addsalespositions', 'individualusers.position_id','=','addsalespositions.addsalesposition_id')
            ->where('users.id','=',$id)
            ->get()->first();

        $a_user = DB::table('individualusers')
            ->join('users', 'users.id', '=', 'individualusers.user_id')
            ->join('addsalespositions', 'addsalespositions.addsalesposition_id', '=', 'individualusers.position_id')
            ->where('individualusers.store_id',$user_dep->store_id)
            ->where('individualusers.department_id',$user_dep->department_id)
            ->where('addsalespositions.position_manager',0)
            // ->whereNotIn('individualusers.user_id', [$id])
            // ->whereNotIn('individualusers.user_id', [$id])
            ->get()->toArray();


        $storeagent = DB::select('select * from `addsalespositions` inner join `individualusers` on `individualusers`.`position_id` = `addsalespositions`.`addsalesposition_id` where `individualusers`.`user_id` = '.$id.'');

        $is_manager = $storeagent[0]->position_manager;

        
        

        if($is_manager == 1){

        	foreach ($a_user as $value) {
                $names[] = $value->name;
                $ids[] = $value->user_id;
            }
           

			$onboardingcontents = DB::select('select * from `individualusers` inner join `boardingcontents` on  `individualusers`.`store_id`=`boardingcontents`.`store_id` where `individualusers`.`user_id` IN ('.implode(',', $ids).') AND `individualusers`.`onboarding_authority`=1  AND `boardingcontents`.`department_id` = individualusers.department_id AND `boardingcontents`.`position_id` = individualusers.position_id');
            
			
        }
        else
        {
            $onboardingcontents = DB::select('select * from `individualusers` inner join `boardingcontents` on  `individualusers`.`store_id`=`boardingcontents`.`store_id` where `individualusers`.`user_id`='.$id.' AND `individualusers`.`onboarding_authority`=1 AND `boardingcontents`.`days_id` = 1 AND `boardingcontents`.`department_id` = individualusers.department_id AND `boardingcontents`.`position_id` = individualusers.position_id');
        }

       
        return view('user.on_boarding_schedule',compact('is_manager','names','responsiblepartys','taskdays','onboardingcontents'));
    }


}