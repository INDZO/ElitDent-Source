import { ChangeDetectorRef, Component, HostListener, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../services/auth.service';
import { BookingService } from '../../services/booking.service';

@Component({
  selector: 'app-booking',
  imports: [FormsModule, RouterLink],
  templateUrl: './booking.component.html',
})
export class BookingComponent implements OnInit {
  readonly backendOrigin = window.location.port === '4200' ? 'http://127.0.0.1:8000' : '';
  readonly weekDays = ['Pon', 'Uto', 'Sre', 'Cet', 'Pet', 'Sub', 'Ned'];
  services: any[] = [];
  teams: any[] = [];
  days: string[] = [];
  slots: string[] = [];

  serviceId = '';
  teamId = '';
  date = '';
  time = '';
  calendarCursor = this.startOfMonth(new Date());
  currentUser: any = null;

  message = '';
  error = '';
  loading = false;
  loadingServices = false;
  loadingTeams = false;
  loadingDays = false;
  loadingSlots = false;
  sidebarOpen = false;

  constructor(
    private auth: AuthService,
    private booking: BookingService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    if (!this.auth.isLoggedIn()) {
      this.router.navigateByUrl('/login');
      return;
    }

    this.loadServices();

    this.auth.me().subscribe({
      next: (response) => {
        this.currentUser = response.user;
        if (this.currentUser?.Role !== 'pacijent') {
          window.location.href = this.backendUrl('/dashboard');
          return;
        }

        this.cdr.detectChanges();
      },
      error: () => {
        localStorage.removeItem('elit_dent_api_token');
        this.router.navigateByUrl('/login');
      },
    });
  }

  private loadServices(): void {
    this.loadingServices = true;
    this.booking.getServices().subscribe({
      next: (response) => {
        this.services = response.services || [];
        this.loadingServices = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        this.loadingServices = false;
        this.error = error?.status === 401
          ? 'Sesija je istekla. Prijavite se ponovo.'
          : 'Nije moguce dobaviti usluge.';
        this.cdr.detectChanges();
      },
    });
  }

  onServiceChange(): void {
    this.resetAfterService();
    if (!this.serviceId) {
      return;
    }

    this.loadingTeams = true;
    this.booking.getTeams(this.serviceId).subscribe({
      next: (response) => {
        this.teams = response.teams || [];
        this.loadingTeams = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.loadingTeams = false;
        this.error = 'Nije moguce dobaviti timove.';
        this.cdr.detectChanges();
      },
    });
  }

  onTeamChange(): void {
    this.days = [];
    this.slots = [];
    this.date = '';
    this.time = '';
    this.message = '';
    this.error = '';

    if (!this.serviceId || !this.teamId) {
      return;
    }

    this.loadingDays = true;
    this.booking.getDays(this.serviceId, this.teamId).subscribe({
      next: (response) => {
        this.days = response.days || [];
        if (this.days.length > 0) {
          this.calendarCursor = this.startOfMonth(this.parseDate(this.days[0]));
        }
        this.loadingDays = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.loadingDays = false;
        this.error = 'Nije moguce dobaviti dane.';
        this.cdr.detectChanges();
      },
    });
  }

  onDateChange(): void {
    this.slots = [];
    this.time = '';
    this.message = '';
    this.error = '';

    if (!this.serviceId || !this.teamId || !this.date) {
      return;
    }

    this.loadingSlots = true;
    this.booking.getSlots(this.serviceId, this.teamId, this.date).subscribe({
      next: (response) => {
        this.slots = response.slots || [];
        this.loadingSlots = false;
        this.cdr.detectChanges();
      },
      error: () => {
        this.loadingSlots = false;
        this.error = 'Nije moguce dobaviti termine.';
        this.cdr.detectChanges();
      },
    });
  }

  selectedService(): any | null {
    return this.services.find((service) => service.id === this.serviceId) || null;
  }

  selectedTeam(): any | null {
    return this.teams.find((team) => String(team.id) === String(this.teamId)) || null;
  }

  selectDay(day: string): void {
    this.date = day;
    this.onDateChange();
  }

  calendarTitle(): string {
    return new Intl.DateTimeFormat('sr-Latn-RS', {
      month: 'long',
      year: 'numeric',
    }).format(this.calendarCursor);
  }

  calendarCells(): Array<{ date: string; dayNumber: number; inMonth: boolean; available: boolean }> {
    const year = this.calendarCursor.getFullYear();
    const month = this.calendarCursor.getMonth();
    const firstDayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    const firstCell = new Date(year, month, 1 - firstDayOffset);
    const availableDays = new Set(this.days);

    return Array.from({ length: 42 }, (_, index) => {
      const cellDate = new Date(firstCell);
      cellDate.setDate(firstCell.getDate() + index);
      const date = this.formatDate(cellDate);

      return {
        date,
        dayNumber: cellDate.getDate(),
        inMonth: cellDate.getMonth() === month,
        available: availableDays.has(date),
      };
    });
  }

  changeCalendarMonth(offset: number): void {
    if ((offset < 0 && !this.canShowPreviousMonth()) || (offset > 0 && !this.canShowNextMonth())) {
      return;
    }

    this.calendarCursor = new Date(
      this.calendarCursor.getFullYear(),
      this.calendarCursor.getMonth() + offset,
      1
    );
  }

  canShowPreviousMonth(): boolean {
    return this.days.length > 0 && this.monthKey(this.calendarCursor) > this.monthKey(this.parseDate(this.days[0]));
  }

  canShowNextMonth(): boolean {
    return this.days.length > 0 && this.monthKey(this.calendarCursor) < this.monthKey(this.parseDate(this.days[this.days.length - 1]));
  }

  selectSlot(slot: string): void {
    this.time = slot;
    this.message = '';
    this.error = '';
  }

  selectedDateTitle(): string {
    if (!this.date) {
      return '';
    }

    return new Intl.DateTimeFormat('sr-Latn-RS', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    }).format(this.parseDate(this.date));
  }

  serviceDuration(): number {
    return Number(this.selectedService()?.trajanje || 0);
  }

  slotEndTime(slot: string): string {
    const [hours, minutes] = slot.split(':').map(Number);
    const endMinutes = hours * 60 + minutes + this.serviceDuration();
    const endHours = Math.floor(endMinutes / 60) % 24;

    return `${String(endHours).padStart(2, '0')}:${String(endMinutes % 60).padStart(2, '0')}`;
  }

  toggleSidebar(): void {
    this.sidebarOpen = !this.sidebarOpen;
  }

  closeSidebar(): void {
    this.sidebarOpen = false;
  }

  @HostListener('document:keydown.escape')
  closeSidebarOnEscape(): void {
    this.closeSidebar();
  }

  submit(): void {
    this.message = '';
    this.error = '';

    if (!this.serviceId || !this.teamId || !this.date || !this.time) {
      this.error = 'Izaberite uslugu, tim, datum i termin.';
      return;
    }

    this.loading = true;
    this.booking
      .bookAppointment({
        service_id: this.serviceId,
        team_id: this.teamId,
        date: this.date,
        time: this.time,
      })
      .subscribe({
        next: (response) => {
          this.message = response.message || 'Termin je zakazan.';
          this.loading = false;
          this.cdr.detectChanges();
          window.location.href = this.backendUrl('/pacijent/termini?status=Zakazan&created=1');
        },
        error: (error) => {
          this.error = error?.error?.message || 'Zakazivanje nije uspelo.';
          this.loading = false;
          this.cdr.detectChanges();
        },
      });
  }

  private resetAfterService(): void {
    this.teams = [];
    this.days = [];
    this.slots = [];
    this.loadingTeams = false;
    this.loadingDays = false;
    this.loadingSlots = false;
    this.teamId = '';
    this.date = '';
    this.time = '';
    this.message = '';
    this.error = '';
  }

  private parseDate(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
  }

  private startOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
  }

  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  private monthKey(date: Date): number {
    return date.getFullYear() * 12 + date.getMonth();
  }

  logout(): void {
    this.auth.logout().subscribe({
      next: () => window.location.href = this.backendUrl('/angular-web-logout'),
      error: () => window.location.href = this.backendUrl('/angular-web-logout'),
    });
  }

  backendUrl(path: string): string {
    return `${this.backendOrigin}${path}`;
  }
}
