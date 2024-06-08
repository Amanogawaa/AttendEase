import { Component, OnInit, Inject } from '@angular/core';
import Swal from 'sweetalert2';
import { AuthserviceService } from '../../../../core/service/authservice.service';
import { MatDialog } from '@angular/material/dialog';
import { CommonModule } from '@angular/common';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { ViewsubmissionsComponent } from '../viewsubmissions/viewsubmissions.component';

@Component({
  selector: 'app-markattendancee',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './markattendancee.component.html',
  styleUrl: './markattendancee.component.css',
})
export class MarkattendanceeComponent implements OnInit {
  datalist: any;

  constructor(
    private service: AuthserviceService,
    @Inject(MAT_DIALOG_DATA) public data: any,
    private dialog: MatDialogRef<MarkattendanceeComponent>,
    private dialog2: MatDialog
  ) {}

  viewAttendances(userId: number) {
    this.dialog2.open(ViewsubmissionsComponent, {
      width: '50%',
      data: {
        selectedUser: userId,
        selectedEvent: this.data.selectedEvent,
      },
    });
  }

  closeDialog() {
    this.dialog.close();
  }

  ngOnInit(): void {
    this.loadData();
  }

  loadData() {
    this.service
      .getUsersByEventAttendance(this.data.selectedEvent)
      .subscribe((res: any) => {
        this.datalist = res.payload;
      });
  }
}
