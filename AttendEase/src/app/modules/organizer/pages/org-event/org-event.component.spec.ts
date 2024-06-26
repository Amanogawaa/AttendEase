import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OrgEventComponent } from './org-event.component';

describe('OrgEventComponent', () => {
  let component: OrgEventComponent;
  let fixture: ComponentFixture<OrgEventComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OrgEventComponent]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(OrgEventComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
