"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var core_1 = require('@angular/core');
var OrganizationsComponent = (function () {
    function OrganizationsComponent(userService) {
        this.userService = userService;
        this.users = [];
        this.currentUser = JSON.parse(localStorage.getItem('currentUser'));
    }
    OrganizationsComponent.prototype.ngOnInit = function () {
        this.loadAllUsers();
    };
    OrganizationsComponent.prototype.deleteUser = function (id) {
        var _this = this;
        this.userService.delete(id).subscribe(function () { _this.loadAllUsers(); });
    };
    OrganizationsComponent.prototype.loadAllUsers = function () {
        var _this = this;
        this.userService.getAll().subscribe(function (users) { _this.users = users; });
    };
    OrganizationsComponent = __decorate([
        core_1.Component({
            moduleId: module.id,
            templateUrl: 'organizations.component.html'
        })
    ], OrganizationsComponent);
    return OrganizationsComponent;
}());
exports.OrganizationsComponent = OrganizationsComponent;
//# sourceMappingURL=organizations.component.js.map