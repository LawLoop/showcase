import { Component, OnInit } from '@angular/core';

import { User, Project } from '../_models/index';
import { UserService, ProjectService } from '../_services/index';

@Component({
    moduleId: module.id,
    templateUrl: 'home.component.html'
})

export class HomeComponent implements OnInit {
    currentUser: User;
    users: User[] = [];
    projects: Project[] = [];

    constructor(private userService: UserService, private projectService: ProjectService) {
        this.currentUser = JSON.parse(localStorage.getItem('currentUser'));
    }

    ngOnInit() {
        this.loadAllUsers();
    }

    deleteUser(id) {
        this.userService.delete(id).subscribe(() => { this.loadAllUsers() });
    }

    copyDAVLink(title) {

    }

    private loadAllUsers() {
        this.userService.getAll().subscribe(users => { this.users = users; });
    }

    private loadAllProjects() {
        this.projectService.getAll().subscribe(projects => { this.projects = projects; });
    }
}