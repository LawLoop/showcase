import { Component, OnInit } from '@angular/core';

import { User, Project } from '../_models/index';
import { ProjectService } from '../_services/index';

@Component({
    moduleId: module.id,
    templateUrl: 'home.component.html'
})

export class HomeComponent implements OnInit {
    currentUser: User;
    users: User[] = [];
    projects: Project[] = [];

    constructor(private projectService: ProjectService) {
        this.currentUser = JSON.parse(localStorage.getItem('currentUser'));
    }

    ngOnInit() {
        //this.loadAllUsers();
        this.loadAllProjects();
    }

    copyDAVLink(title) {
        alert('Link to ' + title);
    }

    private loadAllProjects() {
        this.projectService.getAll().subscribe(projects => { this.projects = projects; });
    }
}