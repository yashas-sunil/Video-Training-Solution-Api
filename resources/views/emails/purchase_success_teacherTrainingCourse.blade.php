<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Course Purchased</title>
    <style>
        body {
            background-color: #fff;
            max-width: 750px !important; 
            overflow: hidden;
            overflow-y: auto;
            margin: 0 auto;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            border: 3px solid #dd5f2b;
            margin: 20px;
        }

        .container a img {
            width: 200px;
        }

        .container .content {
            padding: 20px 40px;
        }

        .container .content h3,
        .container .content a {
            font-size: 18px;
            line-height: 25px;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 25px;
            text-decoration: none;
        }

        .container .content a b {
            font-weight: 900;
            color: #000;
        }

        .container .social_media {
            text-align: center;
            border-top: 4px solid #dd5f2b;
            width: 100%;
        }

        .container .social_media h6 {
            font-size: 15px;
            font-weight: 600;
            color: #2e2e2e;
        }

        .container .social_media ul {
            list-style: none;
            display: table;
            margin: auto;
            text-align: center;
            padding: 0;
        }

        .container .social_media ul li {
            display: table-cell;
        }

        .container .social_media ul li img {
            margin: 0 10px;
            height: 25px;
            width: auto;
        }
    </style>
</head>

<body>
    <div class="container" style="max-width:750px !important;">
        <a href="https://online.jkshahclasses.com" target="_blank"><img src="{{ $message->embed(public_path('logo1.png')) }}" alt="J K Shah Online" />
        </a>
        <div class="content">
            <h3 style="margin-bottom: 10px;">Thanks for enrolling with</h3>
            
            <ul>
                <li><h3 style="margin-top: 0;">JK SHAH  - Teachers Training Programme. We are very happy to have you here!</h3></li>
                <li><h3 style="margin-top: 0;">New batch starts from 2.1.24</h3></li>
            </ul>
            <h3>For further process, kindly give your details in the link below:</h3>
            <h3></h3>
            <h3><a href="https://forms.gle/NqMSb9ErWYmpSQtd7" target="_blank"><span style="color: blue;text-decoration:underline;font-size:16px;" >J.K.SHAH CLASSES FORM</span></a></h3>
            
            <h3>&nbsp;</h3>
            <h3>Regards</h3>
            <h3>Prof. Ketan shah</h3>
            <h3>Course coordinator</h3>


            <div class="social_media">
                <h6>© {{ date('Y') }} JKSHAH ONLINE, ALL RIGHTS RESERVED.</h6>
                <ul>
                    <li><a href="https://www.youtube.com/c/JKShahClassesOnline" target="_blank"><img src="{{ $message->embed(public_path('Youtube1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://www.facebook.com/officialjksc" target="_blank"><img src="{{ $message->embed(public_path('Facebook1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://www.instagram.com/officialjksc/" target="_blank"><img src="{{ $message->embed(public_path('Instagram1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://t.me/jkshahonline" target="_blank"><img src="{{ $message->embed(public_path('Telegram1.png')) }}" alt=""></a></li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>