<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        <?php echo isset($page_header) ? $page_header : '';?>
        <small>Control panel</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="<?php echo base_url('dashboard'); ?>"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active"> <?php echo isset($breadcrumb) ? $breadcrumb : ''; ?></li>
    </ol>
</section> 

<!-- Main content -->
<section class="content">
    <div class="row">
        <!-- left column -->
        <div class="col-md-12">
<?php 
if ($this->session->flashdata('success')) {
    echo notifications('success', $this->session->flashdata('success'));
}
if ($this->session->flashdata('error')) {
    echo notifications('error', $this->session->flashdata('error'));
}
if (validation_errors()) {
    echo notifications('warning', validation_errors('<p>', '</p>'));
}
?>
            <!-- general form elements -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><?php echo isset($panel_heading) ? $panel_heading : '';?> </h3>
                </div><!-- /.box-header -->
<?php
switch ($page) {
    case 'view':
?>
 <div class="row">
    <div class="col-md-8">
        <form class="form-horizontal">
            <div class="box-body"> 
                <div class="form-group">
                    <label class="col-sm-2 control-label">Username</label>
                    <div class="form-control-static">
                        <?php echo isset($username) ? $username : '';?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Name</label>
                    <p class="form-control-static"><?php echo isset($name) ? $name : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Email</label>
                    <p class="form-control-static"><?php echo isset($email) ? $email : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Company</label>
                    <p class="form-control-static"><?php echo isset($company) ? $company : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Phone</label>
                    <p class="form-control-static"><?php echo isset($phone) ? '+62'.$phone : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Group(s)</label>
                    <div class="col-sm-2 form-control-static"><?php echo isset($group_name) ? $group_name : '';?></div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Status</label>
                    <p class="form-control-static"><?php echo isset($active) ? $active : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Last Login</label>
                    <p class="form-control-static"><?php echo isset($last_login) ? $last_login : '';?></p>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Created On</label>
                    <p class="form-control-static"><?php echo isset($created_on) ? $created_on : '';?></p>
                </div>
            </div>

            <div class="box-footer">
                <button type="button" name="edit" class="btn btn-primary" onClick="location.href='<?php echo site_url('adminweb/users/update/'.$id); ?>'">Edit Data</button> &nbsp; &nbsp; 
                <button type="button" name="back" class="btn btn-primary" onclick="history.back();">Back Button</button>
            </div>
        </form>
    </div>
</div>

<?php
    break;
    
    case 'add':
	case 'update':
?>
 <div class="row">
    <div class="col-md-4">
        <form role="form" method="POST" action="<?php echo $action; ?>"> 
            <div class="box-body"> 
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-group">
                        <span class="input-group-addon">@</span>
                        <input type="text" placeholder="username" class="form-control" name="username" id="username"  value = "<?php echo isset($username) ? $username : '';?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                        <input type="email" class="form-control" placeholder="Email" name="email" id="email" value = "<?php echo isset($email) ? $email : '';?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" placeholder="" class="form-control" name="name" id="name" value = "<?php echo isset($name) ? $name : '';?>" />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" id="password" />
                </div>
                <div class="form-group">
                    <label>Retype Password</label>
                    <input type="Password" class="form-control" name="repassword" id="repassword" />
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <div class="input-group">
                        <span class="input-group-addon">+62</span>
                        <input type="text" class="form-control" name="phone" id="phone" value = "<?php echo isset($phone) ? $phone : '';?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" class="form-control" name="company" id="company" value = "<?php echo isset($company) ? $company : '';?>" />
                </div>
                <div class="form-group">
                    <label>Group</label>
                    <?php 
                    echo form_dropdown('groups[]', $groups, isset($group) ? set_value('group', $group) : '1', 'class="form-control chosen-select" id="group" multiple="TRUE"'); 
                    ?>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <?php
                        echo form_dropdown('active', $actives, isset($active) ? set_value('active', $active) : '1', 'class="form-control" id="active"'); 
                    ?>                
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" name="submit" class="btn btn-primary">Submit Button</button>
                <button type="reset" name="reset" class="btn btn-default">Reset Button</button>
            </div>
        </form>
    </div>
</div>
<?php
		break;

    case 'import':
        echo '<div class="box-body">'.
            form_open_multipart(site_url('users/import'), array ('role'=>'form', 'methode'=>'POST')).
            input_file('File Excel', array ('name'=>'userfile', 'id'=>'', 'size'=>'5')).
            button_submit(array ('name'=>'submit', 'value'=>'Upload'), array('name'=>'reset', 'value'=>'Reset')).
            form_close().
            '</div>';
    break;

	default:
		echo '<div class="box-body"><div class="table-responsive" id="table-responsive">';							 							
		echo isset($table) ? $table : '';
		echo '</div></div>';
		break;
}
?>
            </div><!-- /.box -->
        </div><!--/.col (right) -->
    </div>   <!-- /.row -->
</section><!-- /.content -->
