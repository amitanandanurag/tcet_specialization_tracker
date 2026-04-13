<?php
class DBController
{
    /*public $host = "localhost";
    public $user = "tcet_st";
    public $password = "Tcet@1378";
    public $database = " tcet_st";*/

    public $host = "localhost";
    public $user = "root";
    public $password = "";
    public $database = "tcet_st";

    public $conn;

    function __construct()
    {
        $this->conn = $this->connectDB();
    }

    function query($query)
    {
    $result = mysqli_query($this->conn,$query);
    return $result;
    }

     function connectDB()
    {
        $conn = mysqli_connect($this->host,$this->user,$this->password,$this->database);
        return $conn;
    }

    function runQuery($query) {
        $result = mysqli_query($this->conn,$query);
        while($row=mysqli_fetch_assoc($result)) {
            $resultset[] = $row;
        }
        if(!empty($resultset))
            return $resultset;
    }


    function numRows($query)
    {
        $result = mysqli_query($this->conn,$query);
        $rowcount = mysqli_num_rows($result);
        return $rowcount;
    }

    function executeUpdate($query)
    {
        $result = mysqli_query($this->conn,$query);
        return $result;
    }

    function months() 
    {


           $result = $this->runQuery("SELECT * FROM `month_master` WHERE flag=1");
    
           foreach($result as $month_name => $val)
           {
             $id = $result[$month_name]['id'];
           }

           $month_array = array();
           $month=17;
           $below_row=$id-1;
           $rows=$month-$below_row;
          // echo "$rows";
          $result1 = $this->runQuery("SELECT * FROM `month_master` limit $below_row,$rows");

          foreach ($result1 as $month_name => $value) {
            $name = $result1[$month_name]['name'];
            // echo "$name";
            array_push($month_array , $name );
          }
          $result1 = $this->runQuery("SELECT * FROM `month_master` limit 0,$below_row");

          foreach ($result1 as $month_name => $value) {
            $name = $result1[$month_name]['name'];
            // echo "$name";
            array_push($month_array , $name );
          }

          return $month_array;
      } 
    

      function readData($query)
    {
          $result = mysqli_query($this->conn,$query);
         while($row=mysqli_fetch_assoc($result))
       {
            $resultset[] = $row;
           }
          if(!empty($resultset))
            return $resultset;
    }

      function executeInsert($query)
    {
      $result = mysqli_query($this->conn,$query);
      $insert_id = mysqli_insert_id($this->conn);
        return $insert_id;
    }

     public function getSumOfDetails($id)
  {

    $sql = "SELECT IFNULL(SUM(month),0) AS totalmonth FROM `dsms_fees_master` WHERE std_id='$id' AND status = 0";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }


 public function get_fee_receipt($id,$dsms_fees_master) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM $dsms_fees_master WHERE `id` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }


  public function get_unaided_fee_receipt($id,$dsms_fees_master_unaided) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM $dsms_fees_master_unaided WHERE `id` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function get_ext_fee_receipt($id,$dsms_fees_master) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM $dsms_fees_master WHERE `id` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
  

  public function get_bus_fee_receipt($id)
  {
     $stmt = $this->conn->prepare("SELECT * FROM dsms_bus_fee_master WHERE `id` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();

  }


public function get_pdf_report($id)
{

$stmt = $this->conn->prepare("SELECT * FROM  dsms_uniform_fee_master WHERE `id`= '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
 }

public function get_leaving_receipt($id,$leaving_certificate_master) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM $leaving_certificate_master WHERE `general_register_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function get_bonafied_receipt($id,$bonafied_certificate_master) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM $bonafied_certificate_master WHERE `reg_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function get_charcter_receipt($id) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM character_certificate_master WHERE `reg_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function get_sports_receipt($id) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM sports_certificate_master WHERE `reg_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function get_leaving_receipt_2022($id) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM leaving_certificate_master_2022 WHERE `general_register_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
   public function get_leaving_receipt_2021($id) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM leaving_certificate_master_2021 WHERE `general_register_no` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

public function get_id_card_receipt($id) 
  {
   $stmt = $this->conn->prepare("SELECT * FROM id_card_mgt WHERE `std_id` = '$id'");
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }


  public function getSumOfDetails_of_uniform($id)
  {

    $sql = "SELECT IFNULL(SUM(month),0) AS totalmonth FROM `dsms_uniform_fee_master` WHERE std_id='$id'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }


   public function getSumOfDetails_for_bus($id)
  {

    $sql = "SELECT IFNULL(SUM(month),0) AS totalmonth FROM `dsms_bus_fee_master` WHERE std_id='$id'";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }



    function cleanData($data)
    {
          $data = mysqli_real_escape_string($this->conn,strip_tags($data));
          return $data;
      }

       public function loginPage($myusername, $mypassword)
  {
    $mypassword = md5($mypassword);

    $sql = "SELECT * FROM (SELECT * FROM user_master_activate UNION SELECT * FROM user_master ) AS U WHERE U.user_name = ? and U.password = ? AND U.flag = '1' AND U.status = '1'";
    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("ss", $myusername, $mypassword);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      return 1;
    } else {
      return 0;
    }

  } 

  public function delete_student($id,$table)
    {
       $sql = "UPDATE `$table` SET `status`='0' WHERE std_id='$id'"; 
       $stmt = $this->conn->prepare($sql);
       
       if ($stmt->execute()) {
       return 1;
       } else {
        return 2;
        }
    }

  public function resume_student($id,$table)
    {
       $sql = "UPDATE `$table` SET `status`='1' WHERE std_id='$id'"; 
       $stmt = $this->conn->prepare($sql);
       
       if ($stmt->execute()) {
       return 1;
       } else {
        return 2;
        }
    }

  public function update_password($id,$e_pass)
      {
  // $e_pass = md5($e_pass);
  $sql = "update dsms_login set password = '$e_pass' WHERE user_id = '$id'";

    $stmt = $this->conn->prepare($sql);

    if($stmt->execute()){
            return 1;
        }
        else{
            return 2;
        }
      }
 public function update_password_student($id,$e_pass1)
      {
  // $e_pass = md5($e_pass);
  $sql = "update dsms_login set password = '$s_pass1' WHERE user_id = '$id'";

    $stmt = $this->conn->prepare($sql);

    if($stmt->execute()){
            return 1;
        }
        else{
            return 2;
        }
      }

public function get_voucher($id) 
      {
       $stmt = $this->conn->prepare("SELECT * FROM vouchers WHERE `id` = '$id'");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
      }



   public function delete_subject($id)
          {
             $sql = "delete from subject_master WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 

  

   public function delete_result($id)
          {
            $sql = "DELETE FROM `progress_insert` WHERE `reg_no`='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 

       

 public function Edit_Subject($subject_name, $id)
          {
             $sql =  "UPDATE `subject_master` SET `subject_name` ='$subject_name' WHERE `subject_master`.`id` = '$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 

           public function Edit_Class($class_name, $id)
          {
             $sql =  "UPDATE `class_master` SET `class` ='$class_name' WHERE `class_master`.`id` = '$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 


       public function delete_class($id)
          {
             $sql = "delete from class_master WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 



  


           public function Edit_Section($section_name, $id)
          {
             $sql =  "UPDATE `section_master` SET `sections` ='$section_name' WHERE `section_master`.`id` = '$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }  


       public function delete_section($id)
          {
             $sql = "delete from section_master WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 




 
 

  public function Edit_Allocated_Class($select_class,$select_section,$select_teacher,$id)
          {
             $sql =  "UPDATE `manage_class` SET `class_id` ='$select_class',`section_id` ='$select_section',`teacher_id` ='$select_teacher' WHERE `manage_class`.`id` = '$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 


       public function delete_allocated_class($id)
          {
             $sql = "delete from manage_class WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 



     //satish insert tASK
         public function insert_assigned_subject($select_class,$selected)
        {

          $sql = "Select * From subject_assign_table where class_id='$select_class'";

            $stmt = $this->numRows($sql);
           
                if($stmt>=1)
                {            
                 return 4;
                }

 
                else
                {


                     $sql = "INSERT INTO `subject_assign_table` (`sub_id`,`class_id`) VALUES ('{$selected}','$select_class')";

            $stmt = $this->conn->prepare($sql);
           
                if($stmt->execute())
                {
            
                 return 2;
                }

 
                else
                {
                    return 3;
                } 


                }   







          
        }



public function delete_assigned_subject($id)
          {
             $sql = "delete from subject_assign_table WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }

 


        public function update_assigned_subject($select_class,$array_data,$id)
          {
             $sql =  "UPDATE `subject_assign_table` SET `class_id` ='$select_class',`sub_id` ='$array_data' WHERE `subject_assign_table`.`id` = '$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 2;
             } else {
              return 3;
              }
          } 







/////inserting time table

         
         public function insert_timetable($select_clas_tt,$select_section_tt,$timing,$select_sub_mon,$select_sub_tues,$select_sub_wed,$select_sub_thur,$select_sub_fri,$select_sub_sat,$teacher_mon_new,$teacher_tues_new,$teacher_wed_new,$teacher_thur_new,$teacher_fri_new,$teacher_sat_new)
        {

          $sql = "INSERT INTO `time_table` (`class_id`,`section_id`,`time`,`mon_sub`,`mon_teacher`,`tue_sub`,`tue_teacher`,`wed_sub`,`wed_teacher`,`thur_sub`,`thur_teacher`,`fri_sub`,`fri_teacher`,`sat_sub`,`sat_teacher`) VALUES ('$select_clas_tt','$select_section_tt','$timing','$select_sub_mon','$teacher_mon_new','$select_sub_tues','$teacher_tues_new','$select_sub_wed','$teacher_wed_new','$select_sub_thur','$teacher_thur_new','$select_sub_fri','$teacher_fri_new','$select_sub_sat','$teacher_sat_new')";

            $stmt = $this->conn->prepare($sql);
           
                if($stmt->execute())
                {
            
                 return 1;
                }

 
                else
                {
                    return 3;
                } 

       }
          




//////assking question by student
     
         public function ask_qusetion($selected_subject, $student_id, $text_ask_que,$teacher_id)
        {

          $sql = "INSERT INTO `ask_qusetion` (`student_id`,`teacher_id`,`qusetion`,`subject_id`) VALUES ('$student_id','$teacher_id','$text_ask_que','$selected_subject')";

            $stmt = $this->conn->prepare($sql);
           
                if($stmt->execute())
                {
            
                 return 2;
                }

 
                else
                {
                    return 3;
                } 

       }
         




///////answer given by teacher

        public function answer_given($given_answer,$question_id)
      {

  $sql = "update ask_qusetion set answer = '$given_answer' ,status='1' WHERE id = '$question_id'";

    $stmt = $this->conn->prepare($sql);

    if($stmt->execute()){
            return 1;
        }
        else{
            return 2;
        }
      }

 public function delete_que_ans($id)
          {
             $sql = "delete from ask_qusetion WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          } 

            //Adding subject to  teacher
         public function insert_subject_teacher($select_class,$select_section,$select_subject,$selected)
        {

          $sql = "INSERT INTO `subject_assigned_teacher`(`class_id`,`section_id`,`subject_id`,`teacher_id`) VALUES ('$select_class','$select_section','$select_subject','$selected')";

            $stmt = $this->conn->prepare($sql);
           
                if($stmt->execute())
                {
            
                 return 2;
                }

 
                else
                {
                    return 3;
                } 

              }

        public function update_assigned_subject_teacher($select_class,$select_section,$select_subject,$select_teacher,$assigned_subject_id)
          {

             $sql =  "UPDATE `subject_assigned_teacher` SET `class_id` ='$select_class',`section_id` ='$select_section' ,`subject_id` ='$select_subject',`teacher_id` ='$select_teacher' WHERE `subject_assigned_teacher`.`id` = '$assigned_subject_id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) 
             {
              return 2;
             }
             else 
             {
               return 3;
             }

          } 
        public function delete_assigned_subject_teacher($id)
          {
             $sql = "delete from subject_assigned_teacher WHERE id='$id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }
             public function gallerymaster($gallery_name,  $gallery_img)
  {
    $sql = "INSERT INTO `gallery_master` ( `name`,  `img`, `date`) VALUES (NULL, '$gallery_name', '$gallery_img', '0', NOW());";
          // echo($sql);
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 0;
    } else {
      return 1;
    }
  }

  public function course_cat($name)
  {
    $sql = "INSERT INTO `course_cat` (cat_name) VALUES ('$name');";
          // echo($sql);
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }

  public function get_course_cat_id($id)
  {
    $sql = "select * from course_cat where id=$id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function update_course_cat_id($name, $id)
  {
    $sql = "update course_cat set cat_name='$name' where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }


  }

  public function dalete_course_cat_id($id)
  {
    $sql = "delete from course_cat  where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
  public function gallary_cat($name)
  {
    $sql = "INSERT INTO `gallery_cat` (cat_name) VALUES ('$name');";
          // echo($sql);
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }

  }
  public function get_gallary_cat_id($id)
  {
    $sql = "select * from gallery_cat where id=$id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function update_gallary_cat_id($name)
  {
    $sql = "update gallery_cat set cat_name='$name' where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }


  }

  public function dalete_gallery_cat_id($id)
  {
    $sql = "delete from  gallery_cat  where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
  public function addGallery( $gallery_name,  $g_img1 )
  {
    $sql = "INSERT INTO `gallery_master`( `name`, `img1`, `class`) VALUES ('$gallery_name','$g_img1','pics')";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
  public function getGallery($id)
  {
    $sql = "SELECT * FROM gallery_master ";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function updateGallary( $gallery_name,$u_img1)
  {
    $sql = " UPDATE gallery_master set name='$gallery_name',img1 = '$u_img1'";
    $stmt = $this->conn->prepare($sql);

    if ($stmt->execute()) {
      return 1;
    } else {
      return 2;
    }

  }

  public function dalete_gallay_id($id)
  {
    $sql = "DELETE FROM `gallery_master` where gallery_id='$id'";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
 public function addVideos( $video_name)
  {
    $sql = "INSERT INTO `video_master`( `name`, `class`) VALUES ('$video_name','video')";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }

  public function getVideos($id)
  {
    $sql = "SELECT * FROM video_master";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

 public function updateVideos( $video_name)
  {
    $sql = " UPDATE video_master set name ='$video_name'";
    $stmt = $this->conn->prepare($sql);

    if ($stmt->execute()) {
      return 1;
    } else {
      return 2;
    }

  }

  public function dalete_video_id($id)
  {
    $sql = "DELETE FROM `video_master` where id='$id'";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
  public function addGalleryCourceName($course_name, $cat_id)
  {
    $sql = "INSERT INTO `gallary_cource_name` (name,cat_id) VALUES ('$course_name','$cat_id')";
          // echo($sql);
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
  public function get_gallayCName_id($id)
  {
    $sql = "select * from  gallary_cource_name where id=$id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function update_gallayCName_id($name)
  {
    $sql = "update  gallary_cource_name set name='$name' where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }

  public function dalete_gallayCNAme_id($id)
  {
    $sql = "delete from  gallary_cource_name  where id='$id' ";
    $stmt = $this->conn->prepare($sql);
    if ($stmt->execute()) {
      return 1;
    } else {
      return 0;
    }
  }
}
class Gallery extends DBController
{
  //for uploading image
          public function add_gallery($sub_id, $img, $name)
          {
          $sql = "INSERT INTO `gallery_master` (`gallery_id`, `sub_id`, `img`, `name`, `date`) VALUES (NULL, ?, ?, ?, NOW())";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("sss", $sub_id, $img, $name);

          if($stmt->execute()){
            return 1;
          }
          else{
            return 0;
          }
          }

          public function add_cat($cat_name, $img) {
          $sql = "INSERT INTO `category_master` (`cat_id`, `name`, `img`, `date`) VALUES (NULL, ?, ?, NOW())";
          $stmt = $this->conn->prepare($sql);
          $stmt->bind_param("ss", $cat_name, $img);

          if($stmt->execute()){
            return 1;
          }
          else{
            return 0;
          }
          }

          public function add_sub($cat_id, $sub, $img){
            $sql = "INSERT INTO `sub_category` (`sub_id`, `cat_id`, `name`, `img`, `date`) VALUES (NULL, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sss", $cat_id, $sub, $img);

              if($stmt->execute()){
                return 1;
              } else {
                return 0;
              }
          }

          public function delete_cat($cat_id){
             $sql = "DELETE FROM category_master WHERE cat_id = '$cat_id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }
          public function delete_sub($sub_id){
             $sql = "DELETE FROM sub_category WHERE sub_id = '$sub_id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }
          public function delete_gallery($gallery_id){
             $sql = "DELETE FROM gallery_master WHERE gallery_id = '$gallery_id'";

             $stmt = $this->conn->prepare($sql);

             if ($stmt->execute()) {
             return 1;
             } else {
              return 2;
              }
          }

           public function get_cat($u)
          {
           $stmt = $this->conn->prepare("SELECT * FROM category_master WHERE `cat_id` = '$u'");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
          }

           public function update_cat($u, $e_name, $e_img)
          {
             $sql = "UPDATE `category_master` SET `name` = ?, `img` = ? WHERE `cat_id` = '$u'";
              // echo $sql;
             $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $e_name, $e_img);

             if ($stmt->execute()) {
              return 1;
              } else {
              return 2;
             }
          }

          public function get_sub($u) 
          {
           $stmt = $this->conn->prepare("SELECT * FROM sub_category WHERE `sub_id` = '$u'");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
          }

           public function update_sub($u, $cat_id, $name, $img)
          {
             $sql = "UPDATE `sub_category` SET `cat_id` = '$cat_id', `name` = ?, `img` = ? WHERE `sub_id` = '$u'";
             $stmt = $this->conn->prepare($sql);
             $stmt->bind_param("ss", $name, $img);

             if ($stmt->execute()) {
              return 1;
              } else {
              return 2;
             }
          }

          public function get_gallery($u) 
          {
           $stmt = $this->conn->prepare("SELECT * FROM gallery_master WHERE `gallery_id` = '$u'");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
          }

           public function update_gallery($u, $sub_id, $img, $name)
          {
             $sql = "UPDATE `gallery_master` SET `sub_id` = '$sub_id', `img` = ? , `name` = ? WHERE `gallery_id` = '$u'";
             $stmt = $this->conn->prepare($sql);
             $stmt->bind_param("ss", $img, $name);

             if ($stmt->execute()) {
              return 1;
              } else {
              return 2;
             }
          }






}
?>