<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow" />
    <link rel="icon" type="image/png" href="/pimcore/static6/img/favicon/favicon-32x32.png" />

    <title><?= htmlentities($this->getRequest()->getHttpHost(), ENT_QUOTES, 'UTF-8') ?> :: Pimcore</title>

    <link rel="stylesheet" type="text/css" href="/pimcore/static6/js/lib/ext/classic/theme-triton/resources/theme-triton-all.css"/>
    <link rel="stylesheet" type="text/css" href="/pimcore/static6/css/admin.css"/>

    <style type="text/css">
        body {
            min-height: 600px;
        }

        .invalid .x-form-trigger-wrap-default {
            border-right-color: #a61717;
        }

        #credential_error {
            color: #a61717;
        }

        .icon_generate {
            background: url(/pimcore/static6/img/flat-color-icons/engineering.svg) center center no-repeat !important;
        }

        .icon_ok {
            background: url(/pimcore/static6/img/flat-color-icons/ok.svg) center center no-repeat !important;
        }

        .icon_check {
            background: url(/pimcore/static6/img/flat-color-icons/factory.svg) center center no-repeat !important;
        }
    </style>

</head>

<body>

<script type="text/javascript">
    var pimcore_version = "<?= \Pimcore\Version::getVersion() ?>";
</script>

<?php

$scripts = array(
    // library
    "lib/prototype-light.js",
    "lib/jquery.min.js",
    "lib/ext/ext-all.js",
    "pimcore/overrides.js",
    "lib/ext/classic/theme-triton/theme-triton.js",
);

?>

<?php foreach ($scripts as $scriptUrl) { ?>
<script type="text/javascript" src="/pimcore/static6/js/<?= $scriptUrl ?>"></script>
<?php } ?>


<script type="text/javascript">

    var errorMessages = '<b>ERROR:</b><br /><?= implode("<br />", $this->errors) ?>';
    var installdisabled = false;

    <?php if (!empty($this->errors)) { ?>
        installdisabled = true;
    <?php } ?>

    Ext.onReady(function() {

        Ext.tip.QuickTipManager.init();
        Ext.Ajax.setDisableCaching(true);
        Ext.Ajax.setTimeout(900000);


        var passwordGenerator = function ( len ) {
            var length = (len)?(len):(10);
            var string = "abcdefghijklmnopqrstuvwxyz"; //to upper
            var numeric = '0123456789';
            var punctuation = '!@#$%^&*()_+~`|}{[]\:;?><,./-=';
            var password = "";
            var character = "";
            while( password.length<length ) {
                entity1 = Math.ceil(string.length * Math.random()*Math.random());
                entity2 = Math.ceil(numeric.length * Math.random()*Math.random());
                entity3 = Math.ceil(punctuation.length * Math.random()*Math.random());
                hold = string.charAt( entity1 );
                hold = (entity1%2==0)?(hold.toUpperCase()):(hold);
                character += hold;
                character += numeric.charAt( entity2 );
                character += punctuation.charAt( entity3 );
                password = character;
            }
            return password;
        };

        var isValidPassword = function (pass) {
            var passRegExp = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{10,}$/;
            if(!pass.match(passRegExp)) {
                return false;
            }
            return true;
        };

        var validateInput = function () {

            var validInstall = true;
            var validCheckReq = true;
            var credentialError;


            $.each(["mysql_host_socket","mysql_username","mysql_database"], function (index, value) {
                var item = Ext.getCmp(value);
                if(item.getValue().length < 1) {
                    validCheckReq = false;
                    item.addCls("invalid");
                } else {
                    item.removeCls("invalid");
                }
            });

            $.each(["admin_username","admin_password"], function (index, value) {
                var item = Ext.getCmp(value);
                if(item.getValue().length < 1) {
                    validInstall = false;
                    item.addCls("invalid");
                } else {
                    item.removeCls("invalid");
                }
            });

            if(validInstall) {
                var adminPassword = Ext.getCmp("admin_password");
                if (!isValidPassword(adminPassword.getValue())) {
                    validInstall = false;
                    credentialError = "Password must contain at least 10 characters, one lowercase letter, one uppercase letter, one numeric digit, and one special character!";
                }
            }

            var credentialErrorEl = Ext.getCmp("credential_error");
            if(credentialError) {
                credentialErrorEl.update(credentialError);
                credentialErrorEl.show();
            } else {
                credentialErrorEl.hide();
            }

            if(!validCheckReq) {
                validInstall = false;
            }

            if(validInstall) {
                Ext.getCmp("install_button").enable();
            } else {
                Ext.getCmp("install_button").disable();
            }

            if(validCheckReq) {
                Ext.getCmp("check_button").enable();
            } else {
                Ext.getCmp("check_button").disable();
            }
        };

        var win = new Ext.Window({
            width: 450,
            closable: false,
            closeable: false,
            y: 20,
            items: [
                {
                    xtype: "panel",
                    id: "logo",
                    border: false,
                    manageHeight: false,
                    bodyStyle: "padding: 20px 10px 5px 10px",
                    html: '<div align="center"><img width="200" src="/pimcore/static6/img/logo-gray.svg" align="center" /></div>'
                },
                {
                    xtype: "panel",
                    id: "install_errors",
                    border: false,
                    bodyStyle: "color: red; padding: 10px",
                    html: errorMessages,
                    hidden: !installdisabled
                },
                {
                    xtype: "form",
                    id: "install_form",
                    defaultType: "textfield",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            title: "MySQL Settings",
                            xtype: "fieldset",
                            defaults: {
                                width: 380
                            },
                            items: [{
                                    xtype: "combo",
                                    name: "mysql_adapter",
                                    fieldLabel: "Adapter",
                                    store: [
                                        ["Mysqli", "Mysqli"],
                                        ["Pdo_Mysql", "Pdo_Mysql"]
                                    ],
                                    mode: "local",
                                    value: "Pdo_Mysql",
                                    triggerAction: "all"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_host_socket",
                                    id: "mysql_host_socket",
                                    fieldLabel: "Host / Socket",
                                    value: "localhost",
                                    enableKeyEvents: true,
                                    listeners: {
                                        "keyup": validateInput
                                    }
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_port",
                                    fieldLabel: "Port",
                                    value: "3306"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_username",
                                    id: "mysql_username",
                                    fieldLabel: "Username",
                                    enableKeyEvents: true,
                                    listeners: {
                                        "keyup": validateInput
                                    }
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_password",
                                    fieldLabel: "Password"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_database",
                                    id: "mysql_database",
                                    fieldLabel: "Database",
                                    enableKeyEvents: true,
                                    listeners: {
                                        "keyup": validateInput
                                    }
                                }
                            ]
                        },
                        {
                            title: "Admin User",
                            xtype: "fieldset",
                            defaults: {
                                width: 380
                            },
                            items: [
                                {
                                    xtype: "textfield",
                                    name: "admin_username",
                                    id: "admin_username",
                                    fieldLabel: "Username",
                                    value: "admin",
                                    enableKeyEvents: true,
                                    listeners: {
                                        "keyup": validateInput
                                    }
                                },
                                {
                                    xtype: "fieldcontainer",
                                    layout: 'hbox',
                                    items: [{
                                        xtype: "textfield",
                                        width: 340,
                                        name: "admin_password",
                                        id: "admin_password",
                                        fieldLabel: "Password",
                                        enableKeyEvents: true,
                                        listeners: {
                                            "keyup": validateInput
                                        }
                                    }, {
                                        xtype: "button",
                                        width: 32,
                                        style: "margin-left: 8px",
                                        iconCls: "icon_generate",
                                        handler: function () {

                                            var pass;

                                            while(true) {
                                                pass = passwordGenerator(15);
                                                if(isValidPassword(pass)) {
                                                    break;
                                                }
                                            }

                                            Ext.getCmp("admin_password").setValue(pass);
                                            validateInput();
                                        }
                                    }]
                                }, {
                                    xtype: "container",
                                    id: "credential_error",
                                    hidden: true
                                }
                            ]
                        }
                    ]
                }
            ],
            bbar: [{
                    id: "check_button",
                    text: "Check Requirements",
                    iconCls: "icon_check",
                    disabled: true,
                    handler: function () {
                        window.open("/install/check/?" + Ext.urlEncode(Ext.getCmp("install_form").getForm().getFieldValues()));
                    }
                },"->",
                {
                    id: "install_button",
                    text: "<b>Install Now!</b>",
                    iconCls: "icon_ok",
                    disabled: true,
                    handler: function (btn) {

                        btn.disable();
                        Ext.getCmp("install_form").hide();
                        Ext.getCmp("check_button").hide();

                        Ext.getCmp("install_errors").show();
                        Ext.getCmp("install_errors").update("Installing ...");

                        Ext.Ajax.request({
                            url: "/install/index/install",
                            method: "post",
                            params: Ext.getCmp("install_form").getForm().getFieldValues(),
                            success: function (transport) {
                                try {
                                    var response = Ext.decode(transport.responseText);
                                    if (response.success) {
                                        location.href = "/admin/";
                                    }
                                }
                                catch (e) {
                                    Ext.getCmp("install_errors").update(transport.responseText);
                                    Ext.getCmp("install_form").show();
                                    Ext.getCmp("check_button").show();
                                    btn.enable();
                                }
                            },
                            failure: function (transport) {
                                Ext.getCmp("install_errors").update("Failed: " + transport.responseText);
                                Ext.getCmp("install_form").show();
                                Ext.getCmp("check_button").show();
                                btn.enable();
                            }
                        });
                    }
                }
            ],
            listeners: {
                afterrender: function () {
                    // no idea why this is necessary to layout the window correctly
                    window.setTimeout(function () {
                        win.updateLayout();

                        validateInput();
                    }, 1000);
                }
            }
        });

        win.show();
    });

</script>

</body>
</html>
