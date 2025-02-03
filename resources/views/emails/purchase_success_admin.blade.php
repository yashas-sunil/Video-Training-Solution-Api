<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
    <!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <meta content="width=device-width" name="viewport"/>
    <!--[if !mso]><!-->
    <meta content="IE=edge" http-equiv="X-UA-Compatible"/>
    <!--<![endif]-->
    <title></title>
    <!--[if !mso]><!-->
    <!--<![endif]-->
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
        }

        table,
        td,
        tr {
            vertical-align: top;
            border-collapse: collapse;
        }

        * {
            line-height: inherit;
        }

        a[x-apple-data-detectors=true] {
            color: inherit !important;
            text-decoration: none !important;
        }
        p{
            margin :3px;
        }
    </style>
    <style id="media-query" type="text/css">
        @media (max-width: 720px) {

            .block-grid,
            .col {
                min-width: 320px !important;
                max-width: 100% !important;
                display: block !important;
            }

            .block-grid {
                width: 100% !important;
            }

            .col {
                width: 100% !important;
            }

            .col>div {
                margin: 0 auto;
            }

            img.fullwidth,
            img.fullwidthOnMobile {
                max-width: 100% !important;
            }

            .no-stack .col {
                min-width: 0 !important;
                display: table-cell !important;
            }

            .no-stack.two-up .col {
                width: 50% !important;
            }

            .no-stack .col.num4 {
                width: 33% !important;
            }

            .no-stack .col.num8 {
                width: 66% !important;
            }

            .no-stack .col.num4 {
                width: 33% !important;
            }

            .no-stack .col.num3 {
                width: 25% !important;
            }

            .no-stack .col.num6 {
                width: 50% !important;
            }

            .no-stack .col.num9 {
                width: 75% !important;
            }

            .video-block {
                max-width: none !important;
            }

            .mobile_hide {
                min-height: 0px;
                max-height: 0px;
                max-width: 0px;
                display: none;
                overflow: hidden;
                font-size: 0px;
            }

            .desktop_hide {
                display: block !important;
                max-height: none !important;
            }
            p{
                margin :3px;
            }
        }
    </style>
</head>
<body class="clean-body" style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; background-color: #fbfbfb;">
<!--[if IE]><div class="ie-browser"><![endif]-->
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
                                                    <p>Dear J.K.Shah Team,</p><br>
                                                    <p>Student has purchased a new course from online.jkshahclasses.com. Following are the details.</p>
                                                    <br>
                                                    <p>Student Details:</p>
                                                    <p>Name:&nbsp;&nbsp;{{ $attributes['name']}}</p>
                                                    <p>Number:&nbsp;&nbsp;{{ $attributes['phone']}}</p>
                                                    <p>Email:&nbsp;&nbsp;{{ $attributes['email']}}</p>
                                                    <p>Address/Location:&nbsp;&nbsp;{{$attributes['address']}} ,{{$attributes['city']}} </p>
                                                    <br>
                                                    
                                                    @foreach($attributes['packages'] as $row)
                                                  
                                                    <p>Course Details:</p>
                                                    <p>Package:&nbsp;&nbsp;{{ $row['name']}}
                                                    <p>Course:&nbsp;&nbsp;{{ $row['course']['name']}}</p>
                                                    <p>Level:&nbsp;&nbsp;{{ $row['level']['name']}}</p>
                                                    @if(!empty($row['packagetype']['name']))
                                                    <p>Type:&nbsp;&nbsp;{{ $row['packagetype']['name']}}</p>
                                                    @endif
                                                    @if(!empty($row['subject']['name']))
                                                    <p>Subject:&nbsp;&nbsp;{{ @$row['subject']['name']}}</p>
                                                    @endif
                                                    @if(!empty($row['chapter']['name']))
                                                    <p>Chapter:&nbsp;&nbsp;{{ @$row['chapter']['name']}}</p>
                                                    @endif
                                                    <p>Language:&nbsp;&nbsp;{{ $row['language']['name']}}</p>
                                                    <p>Professor(s):&nbsp;&nbsp;  @php
                                                  
                                                        
                                                 $a= $row['professors'];
                                                  $pname='';
                                                    @endphp
                                                   
                                                    @foreach($row['professors'] as $r)
                                                    @php
                                                    $pname=$r->name.','.$pname;
                                                    @endphp

                                                    @endforeach
                                                {{ rtrim($pname,',')}}
                                                </p>
													
                                                    
                                                 
                                                   
                                                   
                                                     @for($i=0;$i<=count($attributes['order_items_details'])-1;$i++)
                                                     @if($attributes['order_items_details'][$i]['package_id']==$row['id'])
                                                     <p>Package Amount:&nbsp;&nbsp;{{$attributes['order_items_details'][$i]['price']}}</p>
                                                     @if($attributes['order_items_details'][$i]['package_discount_amount'])
                                                     <p>Discount Amount:&nbsp;&nbsp;{{$attributes['order_items_details'][$i]['discount_amount']}}</p>
                                                     <p>Total Amount:&nbsp;&nbsp;{{$attributes['order_items_details'][$i]['package_discount_amount']}}</p>
                                                     @endif
                                                     @endif
                                                     @endfor
                                                   
                                                 
                                                    <br>
                                                    @endforeach

                                                    <p>Invoice Details:</p>
                                                    @if($attributes['pendrive_price'])
                                                    <p>Pendrive:&nbsp;&nbsp;{{ $attributes['pendrive_price'] }}</p>
                                                    @endif

                                                    @if($attributes['coupon_amount'])
                                                    <p>Coupon Used:&nbsp;&nbsp;{{ $attributes['coupon_code'] }}</p>
                                                    <p>Coupon Amount:&nbsp;&nbsp;{{ $attributes['coupon_amount'] }}</p>
                                                    @endif
                                                    @if($attributes['holiday_offer_amount'])
                                                    <p>Discount:&nbsp;&nbsp;{{ $attributes['holiday_offername'] }}</p>
                                                    <p>Discount Amount:&nbsp;&nbsp;{{ $attributes['holiday_offer_amount'] }}</p>
                                                    @endif
                                                    @if($attributes['reward_amount'])
                                                    <p>J-Koins:&nbsp;&nbsp; {{number_format($attributes['reward_amount'],2)}}</p>
                                                    @endif

                                                    <p>Total Amount Paid:&nbsp;&nbsp;{{number_format($attributes['net_amount'],2)}}</p>
                                                    @if($attributes['item_type']==2)
                                                    <p>Study Material:&nbsp;&nbsp;{{$attributes['stdy_material_parice']}}</p>
                                                    @endif
                                                    @if($attributes['cgst_amount'] && $attributes['sgst_amount'])
                                                    <p>CGST({{$attributes['cgst']}}%):&nbsp;&nbsp;{{ number_format($attributes['cgst_amount'],2)}}</p>
                                                    <p>SGST({{$attributes['sgst']}}%):&nbsp;&nbsp;{{ number_format($attributes['sgst_amount'], 2)}}</p>
                                                    @elseif($attributes['igst_amount'] )
                                                    <P>IGST({{$attributes['igst']}}%):&nbsp;&nbsp;{{  number_format($attributes['igst_amount'],2)}}</P>
                                                    @endif
                                                    <br>
                                                    <p>Regards,</p>
                                                    <p>Team Datavoice</p>
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
<!--[if (IE)]></div><![endif]-->
</body>
</html>
