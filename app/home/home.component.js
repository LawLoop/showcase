"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var core_1 = require('@angular/core');
var index_1 = require('../_services/index');
var HomeComponent = (function () {
    function HomeComponent(projectService) {
        this.projectService = projectService;
        this.users = [];
        this.projects = [];
        this.currentUser = JSON.parse(localStorage.getItem('currentUser'));
    }
    HomeComponent.prototype.ngOnInit = function () {
        //this.loadAllUsers();
        this.loadAllProjects();
    };
    HomeComponent.prototype.copyDAVLink = function (title) {
        var url = document.location.href + 'dav/Projects/' + title;
        var key = 'Ctrl';
        if (navigator.platform.indexOf('Mac') > -1) {
            key = 'Cmd';
        }
        prompt("Copy to clipboard: " + key + "+C, Enter", url);
    };
    HomeComponent.prototype.loadAllProjects = function () {
        var _this = this;
        this.projectService.getAll().subscribe(function (projects) { _this.projects = projects; });
    };
    HomeComponent = __decorate([
        core_1.Component({
            moduleId: module.id,
            templateUrl: 'home.component.html'
        }), 
        __metadata('design:paramtypes', [index_1.ProjectService])
    ], HomeComponent);
    return HomeComponent;
}());
exports.HomeComponent = HomeComponent;
//# sourceMappingURL=home.component.js.map