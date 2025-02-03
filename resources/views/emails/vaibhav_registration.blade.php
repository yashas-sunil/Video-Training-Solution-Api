<head>
    <link href="https://fonts.googleapis.com/css?family=Ubuntu&display=swap" rel="stylesheet">
    <style type="text/css">
        body {margin: 0; padding: 0; min-width: 100%!important; font-family: 'Ubuntu', sans-serif;}
        img {height: auto;}
        .content {width: 100%; max-width: 660px;}
        .header {padding: 40px 30px 20px 30px;}
        .innerpadding {padding: 30px 30px 30px 30px; line-height: 25px;}
        .borderbottom {border-bottom: 1px solid #f2eeed;}
        .subhead {font-size: 15px; color: #ffffff; letter-spacing: 10px;}
        .h1, .h2, .bodycopy {color: #153643;}
        .h1 {font-size: 33px; line-height: 38px; font-weight: bold;}
        .h2 {padding: 0 0 15px 0; font-size: 24px; line-height: 28px; font-weight: bold;}
        .bodycopy {font-size: 16px; line-height: 22px;}
        .button {text-align: center; font-size: 18px; font-weight: bold; padding: 0 30px 0 30px;}
        .button a {color: #ffffff; text-decoration: none;}
        .footer {padding: 20px 30px 20px 30px;}
        .footercopy {font-size: 14px; color: #ffffff;}
        .footercopy a {color: #ffffff; text-decoration: underline;}
        @media  only screen and (max-width: 550px), screen and (max-device-width: 550px) {
            body[yahoo] .hide {display: none!important;}
            body[yahoo] .buttonwrapper {background-color: transparent!important;}
            body[yahoo] .button {padding: 0px!important;}
            body[yahoo] .button a {background-color: #effb41; padding: 15px 15px 13px!important;}
            body[yahoo] .unsubscribe {display: block; margin-top: 20px; padding: 10px 50px; background: #2f3942; border-radius: 5px; text-decoration: none!important; font-weight: bold;}
        }
    </style>
</head>
<body>
<table bgcolor="#fbfbfb" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="table-layout: fixed; vertical-align: top; min-width: 320px; Margin: 0 auto; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #fbfbfb; width: 100%;" valign="top" width="100%">
    <tbody>
    <tr style="vertical-align: top;" valign="top">
        <td style="word-break: break-word; vertical-align: top;" valign="top">
            <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color:#fbfbfb"><![endif]-->
          
            {{-- FIRST CONTENT--}}
            <div style="background-color:transparent;">
                <div class="block-grid" style="">
                    <div class="col num12" style="">
                        <div style="width:100% !important;">
                            <div style="">
                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding: 2px 5px 2px 5px;">
                                            <div>
                                                <div>
                                                <p>Name:&nbsp;&nbsp;{{ @$attributes['name']}}</p>
                                                    <p>Email Id:&nbsp;&nbsp;{{ @$attributes['email']}}</p>
                                                    <p>Student Contact Number:&nbsp;&nbsp;{{ @$attributes['student_phone']}}</p>
                                                    <p>Parent Contact Number:&nbsp;&nbsp;{{ @$attributes['parent_contact']}}</p>
                                                    <p>Address:&nbsp;&nbsp;{{ @$attributes['address']}}</p>
                                                    <p>City:&nbsp;&nbsp;{{ @$attributes['city']}}</p>
                                                    <p>Pincode:&nbsp;&nbsp;{{ @$attributes['pincode']}}</p>
                                                    <p>Agg % in class X:&nbsp;&nbsp;{{ @$attributes['agg_X']}}</p>
                                                    <p>Agg % in class XI:&nbsp;&nbsp;{{ @$attributes['agg_XI']}}</p>
                                                    @if(!empty($attributes['college']))
                                                    <p>Name of junior college:&nbsp;&nbsp;{{ @$attributes['college']}}</p>
                                                    @else
                                                    <p>Name of junior college:&nbsp;&nbsp;NA</p>
                                                    @endif
                                                    @if(!empty($attributes['college_address']))
                                                    <p>Junior college address:&nbsp;&nbsp;{{ @$attributes['college_address']}}</p>
                                                    @else
                                                    <p>Junior college address:&nbsp;&nbsp;NA</p>
                                                    @endif
                                                    @if(!empty($attributes['income']))
                                                    <p>Income:&nbsp;&nbsp;{{ @$attributes['income']}}</p>
                                                    @endif
                                                    <p>Thank you for registering . We will get back.</p><br>
                                                    <p>Best Regards,<br>JKSHAH Online</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
           
            <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
        </td>
    </tr>
    </tbody>
</table>
</body>
