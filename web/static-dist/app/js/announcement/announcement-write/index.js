webpackJsonp(["app/js/announcement/announcement-write/index"],{"7537a82078b19f2aad2b":function(e,n,t){"use strict";var a=function(){return $("#announcement-write-form").validate({onkeyup:!1,rules:{content:{required:!0},startTime:{required:!0,DateAndTime:!0},endTime:{required:!0,DateAndTime:!0}}})}();!function(){$("#modal").modal("show"),$('a[data-role="announcement-modal"]').click(function(){$("#modal").html("").load($(this).data("url"))}),$(".js-save-btn").click(function(){a.form()&&($(".js-save-btn").button("loading"),$.post($("#announcement-write-form").data("url"),$("#announcement-write-form").serialize(),function(e){window.location.reload()},"json"))})}(),function(e){var n=CKEDITOR.replace("announcement-content-field",{toolbar:"Simple",filebrowserImageUploadUrl:$("#announcement-content-field").data("imageUploadUrl")});n.on("change",function(){$("#announcement-content-field").val(n.getData()),e.form()}),n.on("blur",function(){$("#announcement-content-field").val(n.getData()),e.form()})}(a),function(e){var n=new Date;$("[name=startTime]").datetimepicker({language:"zh",autoclose:!0}).on("hide",function(n){e.form()}),$("[name=startTime]").datetimepicker("setStartDate",n),$("[name=startTime]").datetimepicker().on("changeDate",function(){$("[name=endTime]").datetimepicker("setStartDate",$("[name=startTime]").val().substring(0,16))}),$("[name=endTime]").datetimepicker({autoclose:!0,language:"zh"}).on("hide",function(n){e.form()}),$("[name=endTime]").datetimepicker("setStartDate",n),$("[name=endTime]").datetimepicker().on("changeDate",function(){$("[name=startTime]").datetimepicker("setEndDate",$("[name=endTime]").val().substring(0,16))})}(a)}},["7537a82078b19f2aad2b"]);