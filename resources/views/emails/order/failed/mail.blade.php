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
<h3>Dear Student,</h3>
<br><h4>Your order of {{ $attributes['order_id'] }} is failed . Please make new transaction.</h4></br>


<table bgcolor="#ffffff" class="content" align="center" cellpadding="0" cellspacing="0" border="0">
    <tbody>
    <tr>
        <td bgcolor="#f58457" class="header">
            <table width="70" align="center" border="0" cellpadding="0" cellspacing="0">
                <tbody><tr>
                    <td height="70" style="padding: 0 20px 20px 0;">
                        <a href="{{ $attributes['web'] }}"><img class="fix" src="{{ $attributes['logo'] }}" width="150" border="0" alt=""></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
    </tr>
    <td class="innerpadding bodycopy">
    </td>
    <tr>
        <td class="innerpadding borderbottom" style="padding-top: 0px;">
            <h3 style="text-align: center">STUDY MATERIAL ORDER</h3>
            <table style="width: 100%;">
                <tbody>
                <tr>
                    <td style="width: 50%;"><strong>Student Details</strong></td>
                    <td style="width: 50%;"><strong>Shipping Details</strong></td>
                </tr>
                <tr>
                    <td style="width: 50%;">{{ $attributes['name'] }}</td>
                    <td style="width: 50%;">{{ $attributes['address'] }}</td>
                </tr>
                <tr>
                    <td style="width: 50%;">{{ $attributes['email'] }}</td>
                    <td style="width: 50%;">Area: {{ $attributes['area'] }},</td>
                </tr>
                <tr>
                    <td style="width: 50%;">PH: {{ $attributes['phone'] }}</td>
                    <td style="width: 50%;">Landmark: {{ $attributes['landmark'] }},</td>
                </tr>
                <tr>
                    <td style="width: 50%;"></td>
                    <td style="width: 50%;">City: {{ $attributes['city'] }},</td>
                </tr>
                <tr>
                    <td style="width: 50%;"></td>
                    <td style="width: 50%;">{{ $attributes['state'] }}, {{ $attributes['pin'] }}</td>
                </tr>
                </tbody>
            </table>
            <br />
            <br />
            <table style="width: 100%;">
                <tbody>
                <tr>
                    <td style="width: 100%;" colspan="2"><strong>Order Details</strong></td>
                </tr>
                <!-- <tr>
                    <td>Package #: {{ $attributes['package_id'] }}</td>
                </tr>
                <tr>
                    <td>Package Name: {{ $attributes['package_name'] }}</td>
                </tr> -->
                <tr>
                    <td>Order #: {{ $attributes['order_id'] }}</td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td class="footer" bgcolor="#f58457">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tbody><tr>
                    <td align="center" class="footercopy">
                        © {{ date('Y') }} JKSHAH ONLINE, All rights reserved.
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding: 20px 0 0 0;">
                        <table border="0" cellspacing="0" cellpadding="0">
                            <tbody><tr>
                                <td width="32" style="text-align: center; padding: 0 10px 0 10px;">
                                    <a target="_blank">
                                        <img src="https://www.iconsdb.com/icons/download/white/linkedin-3-32.png" alt="LinkedIn" border="0">
                                    </a>
                                </td>
                                <td width="32" style="text-align: center; padding: 0 10px 0 10px;">
                                    <a target="_blank">
                                        <img src="https://www.iconsdb.com/icons/download/white/facebook-3-32.png" alt="Facebook" border="0">
                                    </a>
                                </td>
                                <td width="32" style="text-align: center; padding: 0 10px 0 10px;">
                                    <a target="_blank">
                                        <img src="https://www.iconsdb.com/icons/download/white/instagram-3-32.png" alt="Instagram" border="0">
                                    </a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
</body>

