import { Component, OnInit } from '@angular/core';
import { DataAnalyticsService } from '../../../../../core/service/data-analytics.service';
import { ChartModule } from 'primeng/chart';
import { TooltipItem } from 'chart.js';

@Component({
  selector: 'app-course',
  standalone: true,
  imports: [ChartModule],
  templateUrl: './course.component.html',
  styleUrl: './course.component.css',
})
export class CourseComponent implements OnInit {
  data: any;
  basicOptions: any;

  constructor(private dataService: DataAnalyticsService) {}

  ngOnInit() {
    this.dataService.getCourse().subscribe((res) => {
      this.processChartData(res.payload);
    });

    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--text-color');

    this.basicOptions = {
      plugins: {
        legend: {
          title: {
            position: 'bottom',
            display: true,
            text: 'Distribution of Students by Course',
            padding: {
              top: 10,
            },
            font: {
              family: 'Inter',
              size: 14,
            },
          },
          position: 'bottom',
          labels: {
            color: textColor,
            usePointStyle: true,
          },
        },
        tooltip: {
          callbacks: {
            title: () => '',
            label: (context: TooltipItem<'bar'>) => {
              // Specify the type for context
              const label = context.label || '';
              const value = (context.raw as number) || 0;
              return `Course: ${label} - ${value} Students`;
            },
          },
        },
      },
    };
  }

  processChartData(payload: any[]) {
    const documentStyle = getComputedStyle(document.documentElement);

    const labels = payload.map((item) => item.course);
    const data = payload.map((item) => item.student_count);

    this.data = {
      labels: labels,
      datasets: [
        {
          data: data,
          backgroundColor: [
            documentStyle.getPropertyValue('--stone-900'),
            documentStyle.getPropertyValue('--stone-800'),
            documentStyle.getPropertyValue('--stone-700'),
            documentStyle.getPropertyValue('--stone-600'),
          ],
        },
      ],
    };
  }
}
