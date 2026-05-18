declare namespace App {
namespace Data {
namespace Finance {
export type MonthlyAverageData = {
month: number,
average: number,
count: number,
};
export type MonthlyBalanceData = {
id: number,
date: string,
amount: number,
comment: string | null,
};
export type PeriodAveragesData = {
last6: number | null,
last12: number | null,
last18: number | null,
overall: number | null,
};
export type StatementData = {
id: number,
date: string,
account: number,
legacy_upgrade: number,
uniqa_sds: number,
uniqa_dds: number,
finax: number,
trading212: number,
};
}
namespace Health {
export type CategoryData = {
id: number,
name: string,
};
export type ExerciseData = {
id: number,
name: string,
category_id: number,
category: App.Data.Health.CategoryData,
};
export type SessionDetailData = {
id: number,
performed_at: string,
exercises: App.Data.Health.SessionExerciseData[],
};
export type SessionExerciseData = {
id: number,
name: string,
completed: boolean,
category: App.Data.Health.CategoryData,
};
export type SessionListItemData = {
id: number,
performed_at: string,
total: number,
completed: number,
};
}
}
}
