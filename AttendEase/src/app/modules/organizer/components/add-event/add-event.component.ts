import { CommonModule } from '@angular/common';
import { Component, Inject, OnInit, ViewEncapsulation } from '@angular/core';
import {
  MAT_DIALOG_DATA,
  MatDialog,
  MatDialogContent,
  MatDialogRef,
} from '@angular/material/dialog';
import {
  FormsModule,
  ReactiveFormsModule,
  FormBuilder,
  Validators,
  AbstractControl,
  ValidatorFn,
  ValidationErrors,
} from '@angular/forms';
import { JsonPipe } from '@angular/common';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatFormFieldModule } from '@angular/material/form-field';
import { EventService } from '../../../../core/service/event.service';
import Swal from 'sweetalert2';
import { Dialog } from '@angular/cdk/dialog';

@Component({
  selector: 'app-add-event',
  standalone: true,
  imports: [
    CommonModule,
    MatDialogContent,
    MatFormFieldModule,
    MatDatepickerModule,
    FormsModule,
    ReactiveFormsModule,
    JsonPipe,
  ],
  templateUrl: './add-event.component.html',
  styleUrl: './add-event.component.css',
  encapsulation: ViewEncapsulation.None,
})
export class AddEventComponent implements OnInit {
  minDate: Date;

  constructor(
    private builder: FormBuilder,
    private eventService: EventService,
    @Inject(MAT_DIALOG_DATA) public data: any,
    private dialogRef: MatDialogRef<AddEventComponent>
  ) {
    this.minDate = new Date();
  }

  ngOnInit(): void {
    if (this.data && this.data.startDate) {
      this.eventForm.patchValue({ event_start_date: this.data.startDate });
    }
  }

  eventForm = this.builder.group({
    event_name: ['', Validators.required],
    event_description: ['', Validators.required],
    event_location: ['', Validators.required],
    event_start_date: [null, [Validators.required, this.futureDateValidator()]],
    event_end_date: [null, [Validators.required, this.futureDateValidator()]],
    event_registration_start: [
      null,
      [Validators.required, this.futureDateValidator()],
    ],
    event_registration_end: [
      null,
      [Validators.required, this.futureDateValidator()],
    ],
    session: ['', Validators.required],
    max_attendees: ['', Validators.required],
  });

  addEvent() {
    if (this.eventForm.valid) {
      this.eventService.addEvent(this.eventForm.value).subscribe(
        (res) => {
          Swal.fire('Success', 'Event added successfully', 'success');
        },
        (error) => {
          Swal.fire(
            'Error',
            error.error.status.message || 'Something went wrong',
            'error'
          );
        }
      );
    } else {
      Swal.fire('Incomplete Form', 'Please fill in all fields', 'warning');
    }
  }

  futureDateValidator(): ValidatorFn {
    return (control: AbstractControl): ValidationErrors | null => {
      const currentDate = new Date();
      const selectedDate = new Date(control.value);
      if (control.value && selectedDate < currentDate) {
        Swal.fire({
          icon: 'warning',
          title: 'Invalid Date',
          text: "You've enter a past date!",
        }).then(() => {
          control.reset();
        });
        return { pastDate: true };
      }
      return null;
    };
  }

  closeDialog() {
    this.dialogRef.close();
  }
}
