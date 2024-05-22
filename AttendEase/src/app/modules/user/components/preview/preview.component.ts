import { Component, Inject, OnInit } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';
import { DatePipe, TitleCasePipe } from '@angular/common';
import { EventService } from '../../../../core/service/event.service';
import { AuthserviceService } from '../../../../core/service/authservice.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-preview',
  standalone: true,
  imports: [DatePipe, TitleCasePipe],
  templateUrl: './preview.component.html',
  styleUrl: './preview.component.css',
})
export class PreviewComponent implements OnInit {
  userId = this.userService.getCurrentUserId();
  eventWithStatus: any;

  constructor(
    @Inject(MAT_DIALOG_DATA) public data: any,
    private service: EventService,
    private userService: AuthserviceService
  ) {}

  ngOnInit(): void {
    console.log(this.userId);
    console.log(this.data);
    this.eventWithStatus = {
      ...this.data.event,
      status: this.getEventStatus(this.data.event),
    };
    console.log(this.eventWithStatus);
  }

  getEventStatus(event: any): string {
    const currentDate = new Date();
    const startDate = new Date(event.event_start_date);
    const endDate = new Date(event.event_end_date);

    if (endDate < currentDate) {
      return 'done';
    } else if (startDate <= currentDate && endDate >= currentDate) {
      return 'ongoing';
    } else {
      return 'upcoming';
    }
  }

  registerForEvent(eventId: number) {
    this.service.registerForEvent(eventId, this.userId).subscribe(
      (response) => {
        Swal.fire('Success', 'Successfully registered', 'success');
        console.log('Registered for event:', response);
      },
      (error) => {
        Swal.fire('Warning', 'Event registration has ended.', 'warning');
      }
    );
  }
}
