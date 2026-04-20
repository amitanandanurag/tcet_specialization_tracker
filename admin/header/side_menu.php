<ul class="sidebar-menu">

    <li class="active"><a href="index.php"><i class="fa fa-user"></i><span style="text-transform: uppercase;"><?php echo $role_name ?></span></a></li>

    <li class="treeview">
        <a href="#">
            <i class="fa fa-graduation-cap" aria-hidden="true"></i> <span>STUDENTS</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="student_admission.php"><i class="fa fa-plus"></i>REGISTER STUDENTS</a></li>
            <li>
                <a href="student-info.php"><i class="fa fa-info-circle"></i>LIST OF STUDENTS</a>
            </li>
            <li>
                <a href="student_concise_details.php"><i class="fa fa-info-circle"></i>CONCISE DETAILS</a>
            </li>
        </ul>
    </li>

    <li class="treeview">
        <a href="#">
            <i class="fa fa-user-secret" aria-hidden="true"></i> <span>ADMIN</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="admin_register.php"><i class="fa fa-plus"></i>REGISTER ADMIN</a></li>
            <li><a href="admin_info.php"><i class="fa fa-info-circle"></i>ADMIN INFO</a></li>
        </ul>
    </li>

    <li class="treeview">
        <a href="#">
            <i class="fa fa-users" aria-hidden="true"></i> <span>COORDINATOR</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="coordinator_register.php"><i class="fa fa-plus"></i>REGISTER COORDINATOR</a></li>
            <li><a href="coordinator_info.php"><i class="fa fa-info-circle"></i>COORDINATOR INFO</a></li>
        </ul>
    </li>

    <li class="treeview">
        <a href="#">
            <i class="fa fa-user" aria-hidden="true"></i> <span>MENTOR</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="mentor_register.php"><i class="fa fa-plus"></i>REGISTER MENTOR</a></li>
            <li><a href="mentor_info.php"><i class="fa fa-info-circle"></i>MENTOR INFO</a></li>
        </ul>
    </li>

    <li class="treeview">
        <a href="#">
            <i class="fa fa-book"></i> <span> SETTINGS</span>
            <span class="pull-right-container">
                <i class="fa fa-angle-right pull-right"></i>
            </span>
        </a>
        <ul class="treeview-menu">
            <li><a href="class_crud_new.php"><i class="fa fa-cogs"></i>MANAGE CLASS</a></li>
        </ul>
    </li>

    </section>