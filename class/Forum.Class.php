<?php

class Forum
{
    var $db;

    //# DB 불러오기
    function Forum($_db)
	{
		$this->db = $_db;
	}

    //# 페이징 및 리스트 출력
    function paging($where)
    {
        if(isset($_GET['page']))
        {
            $page = $_GET['page'];
        }else{$page = 1;}
        
        $list = 10; // 리스트 개수
        $start_num = ($page-1) * $list; //시작번호 (page-1)에서 $list를 곱한다

        $sql = " select * from board ";
        $sql .= " ".$where." order by idx desc limit ".$start_num.", ".$list." "; 
        $result = mysqli_query($this->db->dbconn(), $sql);

        while($board = mysqli_fetch_array($result))
        {            
            $title=$board["title"];

            $sql2 = "select count(*) as cnt from reply where num=".$board['idx']."";
            $result2 = mysqli_query($this->db->dbconn(), $sql2);
		    $reply_count = mysqli_fetch_array($result2);

            $sql3 = "select * from member where id='".$board['id']."'";
            $result3 = mysqli_query($this->db->dbconn(), $sql3);
		    $nick = mysqli_fetch_array($result3);

                if(strlen($title)>30)
                { 
                    $title=str_replace($board["title"],mb_substr($board["title"],0,30,"utf-8")."...",$board["title"]);
                }     

            echo "<tbody><tr class=\"tr\" align=\"center\"><td width=\"400\"><a href=\"?mode=read&idx=".$board['idx']."\">".$title." <b style=\"font-size:15px; color:red;\">[".$reply_count['cnt']."]</b></a></td>";
            echo "<td width=\"200\">".$nick['name']." [ ".$board['id']." ]</td>";
            echo "<td width=\"200\">".$board['date']."</td>";
            echo "<td width=\"100\">".$board['hit']."</td></tr></tbody>";
        }
    }

    //# 페이징 번호 출력
    function paging_num($where, $search_type, $search)
    {
        if(isset($_GET['page']))
        {
            $page = $_GET['page'];
        }else{$page = 1;}
            $sql = "select * from board";
            $sql .= " ".$where." ";
            $result = mysqli_query($this->db->dbconn(), $sql);
            $row_num = mysqli_num_rows($result); // 게시물 수
            $list = 10; // 리스트 개수
            $block_num = 5; // 블록 당 보여줄 페이지 개수
          
            $block = ceil($page/$block_num); // 현재 페이지 블록 구하기
            $block_start = (($block - 1) * $block_num) + 1; // 블록 시작 번호
            $block_end = $block_start + $block_num - 1; // 블록 마지막 번호
          
            $total_page = ceil($row_num / $list); // 페이징한 페이지 수 구하기
            if($block_end > $total_page) $block_end = $total_page; // 빈 페이지가 나오지 않게 총 페이지 수와 마지막 페이지를 같게 맞춰준다
            $total_block = ceil($total_page/$block_num); //블럭 총 개수

            // if($page <= 1){ 
            //     echo "[Front]";
            // }else{
            //     echo "<a href='?page=1'>[Front]</a>";
            // }
          
            if($block <= 1)
            { 
              echo "Back";
            }else{
                $pre = $block_start-1;
                echo "<a style=\"color:red;font-size:15px;\" href='?mode=forum&page=$pre&search_type=$search_type&search=$search'>Back</a>";
            }
            
          
            for($i=$block_start; $i<=$block_end; $i++)
            { 
              if($page == $i){ 
              echo "\n$i\n";
            }else{
              echo "<a style=\"color:red;font-size:15px;\" href='?mode=forum&page=$i&search_type=$search_type&search=$search'>\n$i</a>";
            }}
              
          
            if($block >= $total_block)
            {
              echo "\nNext";
            }else{
              $next = $block_end+1;
              echo "<a style=\"color:red;font-size:15px;\" href='?mode=forum&page=$next&search_type=$search_type&search=$search'>\nNext</a>";
            }
            
            
            // if($page >= $total_page){ 
            //     echo "[End]";
            // }else{
            //     echo "<a href='?page=$total_page'>[End]</a>";
            // }
    }

    //# 글 불러오기
    function read()
    {
        $no = $_GET['idx'];
        
        $sql = "select * from board where idx ='".$no."' ";
        $result = mysqli_query($this->db->dbconn(), $sql);
	    $hit = mysqli_fetch_array($result);
		$hit = $hit['hit'] + 1; 
		$sql ="update board set hit = '".$hit."' where idx = '".$no."'";
        $update = mysqli_query($this->db->dbconn(), $sql);
        
        $sql = "select * from board where idx='".$no."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
		$board = mysqli_fetch_array($result);

        $sql = "select * from member where id='".$board['id']."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
		$nick = mysqli_fetch_array($result);

	    echo "<h2>".$board['title']."</h2>";
		echo "Name: ".$nick['name']." [ ".$board['id']." ] ";
        echo "<br>";
        echo "Date: ".$board['date']." ";
        echo "<br>";
        echo "Hit: ".$board['hit']." ";
        echo "<br>";
        if(!empty($board['file'])){
        echo "<br><img src='comm/upload/$board[file]'><br>";}
        echo "<b><h4>";
        echo nl2br("$board[content]");
        echo "<div id=\"readmodPopup\" style=\"display:none;\">Modify</div>";
        echo "</b></h4><hr>";
    }

    //# 글 수정/삭제 버튼
    function read_btn()
    {
        $no = $_GET['idx'];
        
		$sql = "select * from board where idx='".$no."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
		$board = mysqli_fetch_array($result);

        if($_SESSION['id'] == $board['id']){ 
            echo "<hr><button class=\"btn_read_mod\">Modify</button>\n"; 
            echo "<form action=\"?mode=read&idx=".$board['idx']."\" method=\"post\">
            <input type = \"hidden\" name = \"read_del\" value = \"read_del\"><button>Delete</button></form>";
        }
    }

    //# 글 쓰기
    function write()
    {
        $uid = $_SESSION['id']; 
        $title = $_REQUEST['title'];
        $content = $_REQUEST['content'];
        $date = date('Y-m-d-h-i'); 

        $tmpfile =  $_FILES['imgfile']['tmp_name']; // 임시로 저장된 파일의 위치
        $upfile = $_FILES['imgfile']['name']; // 사용자의 시스템에 있을 때의 파일 이름

        $filename = iconv("UTF-8", "EUC-KR",$_FILES['imgfile']['name']); 
        $folder = "comm/upload/".$filename;

        move_uploaded_file($tmpfile,$folder);

        // *중요* 업로드 할 파일은 파일 권한 숫자값 777로 바꿔주기 (안하면 파일 다운로드 실패)                         

        if($uid && $title && $content){
        $sql = "insert into board(id,title,content,date,file) values('".$uid."','".$title."','".$content."','".$date."','".$upfile."')"; 
        $result = mysqli_query($this->db->dbconn(), $sql);
        echo "<script>alert('Success!');location.href='?mode=forum';</script>";
        }
    }

    //# 글 수정 페이지 불러오기
    function read_mod()
    {
        $no = $_GET['idx'];
	    $sql = "select * from board where idx='$no'";
        $result = mysqli_query($this->db->dbconn(), $sql);
	    $board = mysqli_fetch_array($result);

        echo "<textarea name=\"title\" rows=\"1\" cols=\"40\" placeholder=\"Title\" maxlength=\"100\" required>".$board['title']."</textarea><br>";
        echo "<textarea name=\"content\" cols=\"40\" id=\"readcon\" placeholder=\"Content\" required>".$board['content']."</textarea>";
    }

    //# 글 수정
    function read_mod_ok()
    {
        $no = $_GET['idx'];
        $title = $_REQUEST['title'];
        $content = $_REQUEST['content'];
        $sql = "update board set title='".$title."',content='".$content."' where idx='".$no."'"; 
        $result = mysqli_query($this->db->dbconn(), $sql);

        echo "<script>alert(\"Modified!\");</script>
        <meta http-equiv=\"refresh\" content=\"0 url=?mode=read&idx=".$no."\">";
    }

    //# 글 삭제
    function read_del()
    {
        $no = $_GET['idx'];
	    $sql = "delete from board where idx='$no'";
	    $result = mysqli_query($this->db->dbconn(), $sql);
        $sql = "delete from reply where num='$no'";
	    $result = mysqli_query($this->db->dbconn(), $sql);

        echo "<script>alert(\"Deleted!\");</script>
        <meta http-equiv=\"refresh\" content=\"0 url=?mode=forum\">";
    }

    //# 댓글 불러오기 및 댓글 수정/삭제 버튼
    function reply()
    {
        $no = $_GET['idx'];

        $sql = "select * from reply where num='".$no."' order by idx desc";
        $result = mysqli_query($this->db->dbconn(), $sql);
		while($reply = mysqli_fetch_array($result))
        {
            $sql2 = "select * from member where id='".$reply['id']."'";
            $result2 = mysqli_query($this->db->dbconn(), $sql2);
		    $nick = mysqli_fetch_array($result2);

		    echo "Name: ".$nick['name']." [ ".$reply['id']." ] ";
            echo "<br>";
			echo "Date: ".$reply['date']." ";
            echo "<br>";
            echo "<b><h4>";
            echo nl2br("$reply[content]");
            echo "</b></h4>";

            if($_SESSION['id'] == $reply['id']){ 
                echo "<div id=\"replymodPopup\" style=\"display:none;\">Modify</div>";
                echo "<button class=\"btn_reply_mod\">Modify</button></a>\n";
                echo "<form action=\"?mode=read&idx=".$reply['idx']."\" method=\"post\">
                <input type = \"hidden\" name = \"reply_del\" value = \"reply_del\"><button>Delete</button></form>";
                echo "<br><br><hr>";
            }else{
                echo "<hr>";
            }
        }
    }

    //# 댓글 쓰기
    function reply_ok()
    {
        $no = $_GET['idx'];
        $uid = $_SESSION['id'];
        $date = date('Y-m-d-h-i');
    
        if($no && $uid && $_POST['content']){
            $sql = "insert into reply(num,id,content,date) values('".$no."','".$uid."','".$_POST['content']."','".$date."')";
            $result = mysqli_query($this->db->dbconn(), $sql);
            echo "<script>alert('Success!');location.href='?mode=read&idx=$no';</script>";
        }
    }

    //# 댓글 수정 페이지 불러오가
    function reply_mod()
    {
        $reply_num = $_GET['idx'];

        $sql = "select num from reply where idx='".$reply_num."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
	    $board_num = mysqli_fetch_array($result);

        $sql = "select content from reply where idx='".$reply_num."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
        $reply = mysqli_fetch_array($result);

        echo "<input type=\"hidden\" name=\"reply_no\" value=\"".$reply_num."\">";
        echo "<input type=\"hidden\" name=\"board_no\" value=\"".$board_num['num']."\">";
        echo "<textarea name=\"content\" id=\"replycon\" placeholder=\"Content\" required>".$reply['content']."</textarea><br>";
    }

    //# 댓글 수정
    function reply_mod_ok()
    {
        $rno = $_POST['reply_no'];
        $bno = $_POST['board_no'];
        $date = date('Y-m-d-h-i');

        $sql = "update reply set content='".$_REQUEST['content']."', date='".$date."' where idx = '".$rno."'";
        $result = mysqli_query($this->db->dbconn(), $sql);

        echo "<script>alert('Modified!'); location.replace(\"?mode=read&idx=$bno\");</script>";

    }

    //# 댓글 삭제
    function reply_del()
    {
        $rno = $_GET['idx']; 

        $sql = "select * from reply where idx='".$rno."'";
        $result = mysqli_query($this->db->dbconn(), $sql);
        $reply = mysqli_fetch_array($result);

        $sql = "delete from reply where idx='".$rno."'";
        $result = mysqli_query($this->db->dbconn(), $sql);

        echo "<script>alert('Deleted!'); location.href='?mode=read&idx=".$reply['num']."';</script>";
    }

    //# 댓글 번호 불러오기
    function reply_no()
    {
        $no = $_GET['idx'];
        $sql = "select * from reply where num='".$no."' order by idx desc";
        $result = mysqli_query($this->db->dbconn(), $sql);
        $reply = mysqli_fetch_array($result);
        $rno = $reply['idx'];
        return $rno;
    }

}
