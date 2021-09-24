<div class="dropdown">
    <div class="topbar-item" data-toggle="dropdown" data-offset="0px,0px">
        <div class="btn btn-icon btn-clean h-40px w-40px btn-dropdown">
            <span class="svg-icon svg-icon-lg">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <polygon points="0 0 24 0 24 24 0 24" />
                        <path d="M12,11 C9.790861,11 8,9.209139 8,7 C8,4.790861 9.790861,3 12,3 C14.209139,3 16,4.790861 16,7 C16,9.209139 14.209139,11 12,11 Z" fill="#000000" fill-rule="nonzero" opacity="0.3" />
                        <path d="M3.00065168,20.1992055 C3.38825852,15.4265159 7.26191235,13 11.9833413,13 C16.7712164,13 20.7048837,15.2931929 20.9979143,20.2 C21.0095879,20.3954741 20.9979143,21 20.2466999,21 C16.541124,21 11.0347247,21 3.72750223,21 C3.47671215,21 2.97953825,20.45918 3.00065168,20.1992055 Z" fill="#000000" fill-rule="nonzero" />
                    </g>
                </svg>
            </span>
        </div>
    </div>
    <div class="dropdown-menu p-0 m-0 dropdown-menu-right dropdown-menu-anim-up dropdown-menu-lg p-0">
        <div class="d-flex align-items-center p-8 rounded-top">
            <div class="symbol symbol-md bg-light-primary mr-3 flex-shrink-0">
                <img src="<?= $Admin['user_thumb'] ?>" alt="" />
            </div>
            <div class="text-dark m-0 flex-grow-1 mr-3 font-size-h5"><?= $Admin['user_name'] ?> <?= $Admin['user_lastname'] ?></div>
            <?php /*
              <span class="label label-light-success label-lg font-weight-bold label-inline">3 messages</span>
             */ ?>
        </div>
        <div class="separator separator-solid"></div>
        <div class="navi navi-spacer-x-0 pt-5">
           <?php /*
            <a href="custom/apps/user/profile-1/personal-information.html" class="navi-item px-8">
                <div class="navi-link">
                    <div class="navi-icon mr-2">
                        <i class="flaticon2-calendar-3 text-success"></i>
                    </div>
                    <div class="navi-text">
                        <div class="font-weight-bold">My Profile</div>
                        <div class="text-muted">Account settings and more
                            <span class="label label-light-danger label-inline font-weight-bold">update</span></div>
                    </div>
                </div>
            </a>
            <a href="custom/apps/user/profile-3.html" class="navi-item px-8">
                <div class="navi-link">
                    <div class="navi-icon mr-2">
                        <i class="flaticon2-mail text-warning"></i>
                    </div>
                    <div class="navi-text">
                        <div class="font-weight-bold">My Messages</div>
                        <div class="text-muted">Inbox and tasks</div>
                    </div>
                </div>
            </a>
            <a href="custom/apps/user/profile-2.html" class="navi-item px-8">
                <div class="navi-link">
                    <div class="navi-icon mr-2">
                        <i class="flaticon2-rocket-1 text-danger"></i>
                    </div>
                    <div class="navi-text">
                        <div class="font-weight-bold">My Activities</div>
                        <div class="text-muted">Logs and notifications</div>
                    </div>
                </div>
            </a>
            <a href="custom/apps/userprofile-1/overview.html" class="navi-item px-8">
                <div class="navi-link">
                    <div class="navi-icon mr-2">
                        <i class="flaticon2-hourglass text-primary"></i>
                    </div>
                    <div class="navi-text">
                        <div class="font-weight-bold">My Tasks</div>
                        <div class="text-muted">latest tasks and projects</div>
                    </div>
                </div>
            </a>
            */?>
            <div class="navi-separator mt-3"></div>
            <div class="navi-footer px-8 py-5">
                <a href="dashboard.php?wc=home&logoff=true"  title="Desconectar do <?= ADMIN_NAME; ?>!" class="btn btn-light-primary font-weight-bold">Sair</a>
                <?php /*
                <a href="custom/user/login-v2.html" target="_blank" class="btn btn-clean font-weight-bold">Upgrade Plan</a>
                 */?>
            </div>
        </div>
    </div>
</div>