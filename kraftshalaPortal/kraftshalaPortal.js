var sultanApp = angular.module('sultanApp',[])
    .controller('kraftshalaPortal',
        function ($scope, $http, $window) {

            $scope.registerItems = {};
            $scope.successMsg = '';
            $scope.errorMsg = '';
            $scope.selectTab = 1;
            var fd = new FormData();
            $('select').selectpicker();
            $scope.loginUser = $window.localStorage.getItem('email');

            $scope.registerUsers = function () {
                $scope.registerItems.action = 2;
                $scope.registerItems.userType = $scope.registerItems.subject ? 2:1;
                $http.post("kraftshalaPortal.php",$scope.registerItems).success(function (response) {
                    $scope.successMsg = response.message;
                    $scope.showDialog = true;
                });
            };
            $scope.hideDialog = function () {
                $scope.registerItems = {};
                $scope.showDialog = false;
            }
            $scope.userLogin = function () {
                $window.localStorage.setItem('email',$scope.registerItems.email);
                $http.get("kraftshalaPortal.php?flag=1&name="+$scope.registerItems.name+"&email="+$scope.registerItems.email).success(function (response){
                    $scope.userExists = response.data.userExists;
                    if($scope.userExists){
                        $window.location.href = response.data.userType == 2 ? 'instructorDashboard.html':'studentDashboard.html';
                    }
                    else $scope.errorMsg = 'User is not registered yet!';
                });
            }
            $scope.closeLogin = function () {
                $scope.registerItems = {};
                $scope.errorMsg='';
                $scope.loginForm = false;
                $scope.submitForm = false;
            }
            $scope.imageUpload = function () {
                var files = document.getElementById('file').files[0];
                fd.append('file',files);
            }
            $scope.addAssignment = function (){
                fd.append('name',$scope.registerItems.name);
                fd.append('subject',$scope.registerItems.subject);
                var deadline = $scope.convertDate($scope.registerItems.deadline);
                fd.append('deadline',deadline);
                fd.append('action',1);
                $http({
                    method: 'post',
                    url: 'kraftshalaPortal.php',
                    data: fd,
                    headers: {'Content-Type': undefined},
                    }).then(function successCallback(response) {
                        $scope.successMsg = response.data.message;
                        if(response.data.error) $scope.errorMsg = response.data.message;
                        $scope.showDialog = true;
                    });
            }
            $scope.convertDate = function (str) {
                var date = new Date(str),
                mnth = ("0" + (date.getMonth() + 1)).slice(-2),
                day = ("0" + date.getDate()).slice(-2);
                return [date.getFullYear(), mnth, day].join("-");
            }
            $scope.goToLoginPage = function (){
                $window.location.href = 'kraftshalaPortal.html';
            }
            $scope.upcomingSubmissions = function () {
                $http.get("kraftshalaPortal.php?flag=2").success(function (response){
                    $scope.assignmentsData = response.data;
                });
            }
            // $scope.upcomingSubmissions();
            $scope.submitAssignment = function(){
                fd.append('studentName',$scope.registerItems.studentName);
                fd.append('assignment_id',$scope.registerItems.assignment_id);
                fd.append('email',$scope.registerItems.email);
                fd.append('assignment_deadline',$scope.registerItems.assignment_deadline);
                fd.append('action',3);
                $http({
                    method: 'post',
                    url: 'kraftshalaPortal.php',
                    data: fd,
                    headers: {'Content-Type': undefined},
                    }).then(function successCallback(response) {
                        if(response.data.error){
                            $scope.errorMsg = response.data.message;
                            $scope.submitForm = true;
                        }
                        else {
                            $scope.successMsg = response.data.message;
                            $scope.showDialog = true;
                        }
                    });
            }
            $scope.submitAssignForm = function(index, assignId,deadline){
                $scope.submitForm = true;
                $scope.registerItems.assignment_id = assignId;
                $scope.registerItems.assignment_deadline = deadline;
            }
            $scope.submissions = function (action = null,search = null) {
                var params = '';
                if(action == 1) params = '&user='+$scope.loginUser;
                else if(action == 2) params = '&search='+search;
                $http.get("kraftshalaPortal.php?flag=3"+params).success(function (response){
                    $scope.submissionsAssign = response.data;
                    if(!$scope.submissionsAssign) $scope.arrayLength = 0;
                });
            }
            $scope.submitGrade = function (grade,email,assignId,index) {
                $scope.registerItems.email = email;
                $scope.registerItems.assignment_id = assignId;
                $scope.registerItems.action = 4;
                $scope.registerItems.stGrade = grade;
                $http.post("kraftshalaPortal.php",$scope.registerItems).success(function (response) {
                    $scope.submissionsAssign[index].grade = response.data.grade;
                });
            }
        });