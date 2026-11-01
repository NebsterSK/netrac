declare namespace App {
    namespace Data {
        namespace Buzerlistok {
            export type GoalData = {
                id: number;
                name: string;
                marks: App.Data.Buzerlistok.MarkData[];
            };
            export type MarkData = {
                marked_on: string;
                status: App.Enums.Buzerlistok.MarkStatus;
            };
            export type WeekData = {
                id: number;
                starts_on: string;
                days: string[];
                goals: App.Data.Buzerlistok.GoalData[];
            };
        }
        namespace Finance {
            export type AssetData = {
                id: number;
                user_id: number;
                name: string;
                color: string | null;
                tracks_monthly_balance: boolean;
                statements_count: number | null;
            };
            export type ExpenseCategoryBreakdownData = {
                id: number;
                name: string;
                color: string | null;
                total: number;
                expenses: App.Data.Finance.ExpenseSliceData[];
            };
            export type ExpenseCategoryData = {
                id: number;
                name: string;
                color: string | null;
                expenses_count: number | null;
            };
            export type ExpenseData = {
                id: number;
                user_id: number;
                name: string;
                color: string | null;
                expense_category_id: number;
                amount: number;
                expenseCategory: App.Data.Finance.ExpenseCategoryData;
                created_at: string;
                updated_at: string;
            };
            export type ExpenseSliceData = {
                id: number;
                name: string;
                color: string | null;
                amount: number;
            };
            export type MonthlyAverageData = {
                month: number;
                average: number;
                count: number;
            };
            export type MonthlyBalanceData = {
                date: string;
                amount: number;
                note: string | null;
                is_draft: boolean;
            };
            export type NetWorthMonthData = {
                month: string;
                statements: App.Data.Finance.StatementData[];
                amounts: Record<number, number>;
                total: number;
                gain: number | null;
                gain_percent: number | null;
                is_draft: boolean;
                is_complete: boolean;
            };
            export type OwnerData = {
                id: number;
                name: string;
                color: string;
            };
            export type PeriodAveragesData = {
                last6: number | null;
                last12: number | null;
                last18: number | null;
                overall: number | null;
            };
            export type StatementData = {
                id: number;
                user_id: number;
                date: string;
                amounts: Record<number, number>;
                note: string | null;
                is_draft: boolean;
            };
        }
        namespace Health {
            export type ExerciseCategoryData = {
                id: number;
                name: string;
                priority: number;
            };
            export type ExerciseData = {
                id: number;
                name: string;
                exercise_category_id: number;
                movement_pattern: App.Enums.Health.MovementPattern | null;
                exerciseCategory: App.Data.Health.ExerciseCategoryData;
                created_at: string;
                updated_at: string;
            };
            export type SessionDetailData = {
                id: number;
                performed_at: string;
                exercises: App.Data.Health.SessionExerciseData[];
            };
            export type SessionExerciseData = {
                id: number;
                name: string;
                completed: boolean;
                exerciseCategory: App.Data.Health.ExerciseCategoryData;
            };
            export type SessionListItemData = {
                id: number;
                performed_at: string;
                total: number;
                completed: number;
            };
        }
    }
    namespace Enums {
        namespace Buzerlistok {
            export type MarkStatus = 'success' | 'partial' | 'fail';
        }
        namespace Health {
            export type ExerciseCategory =
                | 'Chest'
                | 'Shoulders'
                | 'Legs'
                | 'Back'
                | 'Core'
                | 'Cardio'
                | 'Arms';
            export type MovementPattern = 'Push' | 'Pull' | 'Squat' | 'Hinge';
        }
    }
}
